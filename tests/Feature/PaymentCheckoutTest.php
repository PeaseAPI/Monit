<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M16 §11 支付处理器接线审计：
 * 22 处理器 checkout 放行（6 专线 + wechat/alipay + 14 通用托管型）
 */
class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => 'Payer', 'email' => 'payer@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
    }

    protected function makePlan(): Plan
    {
        return Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1, 'is_enabled' => true,
            'prices' => ['USD' => ['monthly' => 9.99, 'annual' => 99.99, 'lifetime' => 199.99]],
        ]);
    }

    public function test_payments_index_renders_with_plan_prices(): void
    {
        $this->makePlan();
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/payments');

        $response->assertOk();
        $response->assertSee('9.99');
        // 22 个处理器全部出现在选项中（规格 §11）
        foreach (config('monit.payment.supported_processors') as $processor) {
            $response->assertSee('value="'.$processor.'"', false);
        }
    }

    public function test_checkout_supports_all_22_processors_via_validation(): void
    {
        $this->makePlan();
        $user = $this->makeUser();

        $this->assertCount(22, config('monit.payment.supported_processors'));
        $this->assertSame(
            config('monit.payment.supported_processors'),
            \App\Http\Controllers\PaymentController::PROCESSORS
        );

        // 未知处理器被拒
        $this->actingAs($user)->post('/payments/checkout', [
            'plan_id' => 'pro', 'processor' => 'foo', 'frequency' => 'monthly',
        ])->assertInvalid('processor');

        // 缺 frequency 被拒
        $this->actingAs($user)->post('/payments/checkout', [
            'plan_id' => 'pro', 'processor' => 'stripe',
        ])->assertInvalid('frequency');
    }

    public function test_checkout_generic_processor_creates_order_and_renders_page(): void
    {
        $this->makePlan();
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/payments/checkout', [
            'plan_id' => 'pro',
            'processor' => 'paddle',
            'frequency' => 'monthly',
        ]);

        $response->assertOk();
        $response->assertViewIs('payments.processor-checkout');
        $response->assertViewHas('processor', 'paddle');

        $this->assertDatabaseHas('payments', [
            'payment_processor' => 'paddle',
            'status' => 0,
            'total_amount' => 9.99,
        ]);
    }

    public function test_checkout_rejects_unconfigured_wechat(): void
    {
        $this->makePlan();
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/payments/checkout', [
            'plan_id' => 'pro',
            'processor' => 'wechat',
            'frequency' => 'monthly',
        ]);

        // 未配置微信支付 → 返回错误（订单已创建但网关跳转被拒）
        $response->assertRedirect();
        $response->assertSessionHasErrors('processor');
    }
}

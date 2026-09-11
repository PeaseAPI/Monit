<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Plan;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A2/B1：结账页支付方式默认选择跟随后台开关
 * - 前台仅展示后台启用的处理器，默认选中第一个启用项
 * - 计费周期 hidden 字段与 JS 初始渲染使用后台 default_payment_frequency（原为硬编码 monthly）
 * - checkout 服务端强校验：未启用的处理器直接拒绝（防绕过前端）
 */
class PaymentDefaultProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => '支付测试用户', 'email' => uniqid('pay-').'@pay.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ]);
    }

    protected function makePlan(): Plan
    {
        return Plan::create([
            'plan_id' => 'starter-'.uniqid(),
            'name' => 'Starter',
            'prices' => ['CNY' => ['monthly' => 29, 'annual' => 290, 'lifetime' => 580]],
            'settings' => ['websites_limit' => 5],
            'order' => 1,
            'is_enabled' => true,
        ]);
    }

    public function test_only_enabled_processors_are_listed_with_first_as_default(): void
    {
        Settings::set('payment.offline_is_enabled', 'true');
        Settings::set('payment.paypal_is_enabled', 'false');
        $this->makePlan();

        $this->actingAs($this->makeUser())->get('/payments')
            ->assertOk()
            // 启用的 offline 出现在下拉选项里
            ->assertSee('value="offline"', false)
            // 未启用的 paypal 不再无条件列出（旧实现渲染全部 22 个处理器）
            ->assertDontSee('value="paypal"', false);
    }

    public function test_disabled_default_frequency_is_rendered_not_hardcoded_monthly(): void
    {
        Settings::set('payment.default_payment_frequency', 'annual');
        Settings::set('payment.offline_is_enabled', 'true');
        $this->makePlan();

        $this->actingAs($this->makeUser())->get('/payments')
            ->assertOk()
            ->assertSee('name="frequency" value="annual"', false)
            ->assertSee('data-default-freq="annual"', false);
    }

    public function test_checkout_rejects_processor_that_is_not_enabled(): void
    {
        $plan = $this->makePlan();
        // offline 未启用
        Settings::set('payment.offline_is_enabled', 'false');

        $this->actingAs($this->makeUser())
            ->post('/payments/checkout', [
                'plan_id' => $plan->plan_id,
                'processor' => 'offline',
                'frequency' => 'monthly',
            ])
            ->assertSessionHasErrors('processor');
    }

    public function test_checkout_accepts_enabled_processor(): void
    {
        $plan = $this->makePlan();
        Settings::set('payment.offline_is_enabled', 'true');
        Settings::set('payment.offline_instructions', '请转账后上传凭证');

        $this->actingAs($this->makeUser())
            ->post('/payments/checkout', [
                'plan_id' => $plan->plan_id,
                'processor' => 'offline',
                'frequency' => 'monthly',
            ])
            ->assertSessionHasNoErrors();

        $paidUser = auth()->user();
        $this->assertNotNull($paidUser);
        $this->assertDatabaseHas('payments', [
            'user_id' => $paidUser->user_id,
            'payment_processor' => 'offline',
        ]);
    }

    public function test_credential_based_gateways_are_enabled_only_when_configured(): void
    {
        // 无凭据时 mollie 不可用
        config(['services.mollie' => ['key' => '']]);
        $this->assertNotContains('mollie', PaymentController::enabledProcessors());

        // 配置凭据后可用
        config(['services.mollie' => ['key' => 'test_api_key']]);
        $this->assertContains('mollie', PaymentController::enabledProcessors());
    }
}

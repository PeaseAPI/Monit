<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 待处理订单「继续支付」（payments.resume）：
 * 归属校验 / 已完成重定向 / 按原处理器分发 / 支付记录页按钮渲染
 */
class PaymentResumeTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(): User
    {
        return User::create([
            'name' => 'Payer', 'email' => 'payer@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
    }

    protected function makePayment(User $user, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'plan_id' => 'free',
            'payment_processor' => 'offline',
            'type' => 'one_time',
            'frequency' => 'monthly',
            'total_amount' => '71.36',
            'currency' => 'CNY',
            'status' => 0,
            'datetime' => now(),
        ], $overrides));
    }

    public function test_resume_rejects_non_owner(): void
    {
        $owner = $this->makeUser();
        $other = User::create([
            'name' => 'Other', 'email' => 'other@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        $payment = $this->makePayment($owner);

        $this->actingAs($other)
            ->get('/payments/'.$payment->payment_id.'/pay')
            ->assertForbidden();
    }

    public function test_resume_redirects_completed_order_back_to_history(): void
    {
        $user = $this->makeUser();
        $payment = $this->makePayment($user, ['status' => 1]);

        $this->actingAs($user)
            ->get('/payments/'.$payment->payment_id.'/pay')
            ->assertRedirect(route('payments.history'));
    }

    public function test_resume_offline_renders_instructions_with_existing_order(): void
    {
        $user = $this->makeUser();
        $payment = $this->makePayment($user, ['payment_processor' => 'offline']);

        $response = $this->actingAs($user)
            ->get('/payments/'.$payment->payment_id.'/pay');

        $response->assertOk();
        $response->assertViewIs('payments.offline-instructions');
        // 复用现有订单：不新建 Payment 记录
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_resume_unconfigured_wechat_shows_error_without_new_order(): void
    {
        $user = $this->makeUser();
        $payment = $this->makePayment($user, ['payment_processor' => 'wechat']);

        $response = $this->actingAs($user)
            ->get('/payments/'.$payment->payment_id.'/pay');

        $response->assertRedirect();
        $response->assertSessionHasErrors('processor');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_history_shows_resume_action_only_for_pending_orders(): void
    {
        $user = $this->makeUser();
        $pending = $this->makePayment($user, ['payment_processor' => 'alipay']);
        $completed = $this->makePayment($user, ['payment_processor' => 'alipay', 'status' => 1]);

        $response = $this->actingAs($user)->get('/payments/history');

        $response->assertOk();
        $this->assertStringContainsString(
            '/payments/'.$pending->payment_id.'/pay',
            $response->getContent()
        );
        $this->assertStringNotContainsString(
            '/payments/'.$completed->payment_id.'/pay',
            $response->getContent()
        );
    }
}

<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 安全审计周期 #14（carry-over）：handleExternalPaymentNotification 记账口径
 *
 * 缺陷：外部回查入账路径（mercadopago/midtrans/myfatoorah/klarna/plisio/
 * onepay/flutterwave/payu/iyzico 等）只置 status=1 + 激活套餐，
 * 不累计 users.payment_total_amount、不派发平台 webhook —— 同一笔支付
 * 走不同网关记账结果不同。统一收口到 settlePayment（非安全，记账一致性）。
 */
class ExternalNotificationSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'External Payer', 'email' => 'extpayer@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $this->payment = Payment::create([
            'user_id' => $this->user->user_id,
            'name' => $this->user->name, 'email' => $this->user->email,
            'plan_id' => 'pro', 'payment_processor' => 'mercadopago',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'USD', 'external_id' => 'mp_ext_1',
            'datetime' => now(),
        ]);
    }

    #[Test]
    public function external_notification_accumulates_payment_total(): void
    {
        app(PaymentService::class)->handleExternalPaymentNotification('mercadopago', 'mp_ext_1');

        $this->assertSame(1, (int) $this->payment->fresh()->status);
        $this->assertSame(9.99, (float) $this->user->fresh()->payment_total_amount);
        $this->assertSame('mercadopago', $this->user->fresh()->payment_processor);
        $this->assertSame('USD', $this->user->fresh()->payment_currency);
        $this->assertSame('pro', $this->user->fresh()->plan_id);
    }

    #[Test]
    public function repeated_notification_does_not_double_count(): void
    {
        $service = app(PaymentService::class);

        $service->handleExternalPaymentNotification('mercadopago', 'mp_ext_1');
        $service->handleExternalPaymentNotification('mercadopago', 'mp_ext_1');

        $this->assertSame(9.99, (float) $this->user->fresh()->payment_total_amount);
    }

    #[Test]
    public function unknown_external_id_is_ignored(): void
    {
        app(PaymentService::class)->handleExternalPaymentNotification('mercadopago', 'no_such_id');

        $this->assertSame(0, (int) $this->payment->fresh()->status);
        $this->assertNull($this->user->fresh()->payment_total_amount);
    }
}

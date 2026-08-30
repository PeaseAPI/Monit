<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 支付回调安全回归测试（M24 安全加固轮）：
 * 1. fail-closed：全部回调密钥未配置/签名缺失或不符 → 400，订单不得入账
 * 2. Stripe 官方 HMAC 验签（正确签名放行、错误签名/过期时间戳拒绝）
 * 3. handlePaymentSuccess 幂等（重复回调不重复累计/续期）
 * 4. 激活以 payments.plan_id（购买快照）为准，而非用户当前套餐
 * 5. uploadProof 归属校验（IDOR 防御）
 */
class PaymentWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $planId = 'free'): User
    {
        return User::create([
            'name' => 'Payer',
            'email' => 'payer@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => $planId,
        ]);
    }

    protected function makePlan(string $planId = 'pro'): Plan
    {
        return Plan::create([
            'plan_id' => $planId,
            'name' => ucfirst($planId),
            'order' => 1,
            'is_enabled' => true,
            'prices' => ['USD' => ['monthly' => 9.99, 'annual' => 99.99, 'lifetime' => 199.99]],
            'settings' => ['no_resources_limit' => -1],
        ]);
    }

    protected function makePaidPendingPayment(User $user, string $planId = 'pro'): Payment
    {
        $this->makePlan($planId);

        return Payment::create([
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'plan_id' => $planId,
            'payment_processor' => 'stripe',
            'type' => 'one_time',
            'frequency' => 'monthly',
            'status' => 0,
            'total_amount' => 9.99,
            'currency' => 'USD',
            'datetime' => now(),
        ]);
    }

    /* ---------------- Stripe 验签 ---------------- */

    public function test_stripe_webhook_rejects_forged_payment_without_signature(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        // 伪造成功通知（无签名头）
        $this->postJson('/webhooks/stripe', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_forged',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
            ]],
        ])->assertStatus(400);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_stripe_webhook_rejects_invalid_signature(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        $body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_x',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
            ]],
        ]);

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => 't=' . time() . ',v1=' . Str::random(64),
        ]), $body)->assertStatus(400);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_stripe_webhook_rejects_stale_timestamp(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        $body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_x',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
            ]],
        ]);

        $stale = time() - 3600;
        $sig = hash_hmac('sha256', $stale . '.' . $body, 'whsec_test');

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => "t={$stale},v1={$sig}",
        ]), $body)->assertStatus(400);
    }

    public function test_stripe_webhook_accepts_valid_signature_and_marks_paid(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test');
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        $body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_real',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
            ]],
        ]);

        $t = time();
        $sig = hash_hmac('sha256', $t . '.' . $body, 'whsec_test');

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => "t={$t},v1={$sig}",
        ]), $body)->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 1]);
    }

    /* ---------------- 通用回调 fail-closed ---------------- */

    public function test_razorpay_webhook_rejects_without_signature(): void
    {
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        $this->postJson('/webhooks/razorpay', [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_x',
                'notes' => ['payment_id' => (string) $payment->payment_id],
            ]]],
        ])->assertStatus(400);

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 0]);
    }

    public function test_paystack_webhook_rejects_when_unconfigured(): void
    {
        config()->set('services.paystack.secret_key', null);

        $this->postJson('/webhooks/paystack', ['event' => 'charge.success'])
            ->assertStatus(400);
    }

    public function test_lemonsqueezy_webhook_fail_closed_without_secret(): void
    {
        config()->set('services.lemonsqueezy.webhook_secret', null);

        $this->postJson('/webhooks/lemonsqueezy', ['meta' => ['event_name' => 'order_created']])
            ->assertStatus(400);
    }

    public function test_midtrans_rejects_bad_signature_but_accepts_valid_one(): void
    {
        config()->set('services.midtrans.server_key', 'mid-server-test');
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);
        $payment->update(['payment_processor' => 'midtrans', 'external_id' => 'order-123']);

        $payload = [
            'order_id' => 'order-123',
            'status_code' => '200',
            'gross_amount' => '9.99',
            'transaction_status' => 'settlement',
        ];

        // 错误签名
        $this->postJson('/webhooks/midtrans', $payload + ['signature_key' => Str::random(64)])
            ->assertStatus(400);

        // 正确签名（sha512(serverKey.order_id.status_code.gross_amount)）
        $payload['signature_key'] = hash('sha512', 'mid-server-testorder-1232009.99');

        $this->postJson('/webhooks/midtrans', $payload)->assertOk();

        $this->assertDatabaseHas('payments', ['payment_id' => $payment->payment_id, 'status' => 1]);
    }

    public function test_paddle_classic_rejects_without_valid_signature(): void
    {
        $this->postJson('/webhooks/paddle', [
            'alert_name' => 'payment_succeeded',
            'passthrough' => json_encode(['payment_id' => 1]),
            'order_id' => 'order-x',
        ])->assertStatus(400);
    }

    public function test_generic_hmac_guarded_endpoints_reject_unsigned_requests(): void
    {
        foreach (['crypto', 'myfatoorah', 'klarna', 'plisio', 'revolut', 'onepay', 'iyzico', 'mercadopago'] as $endpoint) {
            $this->postJson("/webhooks/{$endpoint}", [])->assertStatus(400);
        }
    }

    public function test_yookassa_payment_succeeded_requires_verified_server_lookup(): void
    {
        // 成功事件必须经服务端回查；未配置商户密钥 → 拒绝（fail-closed）
        config()->set('services.yookassa.shop_id', null);
        config()->set('services.yookassa.secret_key', null);

        $this->postJson('/webhooks/yookassa', [
            'event' => 'payment.succeeded',
            'object' => ['id' => 'fake-id', 'metadata' => ['payment_id' => '1']],
        ])->assertStatus(400);
    }

    /* ---------------- 幂等与套餐激活 ---------------- */

    public function test_handle_payment_success_is_idempotent(): void
    {
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);

        $service = app(PaymentService::class);

        $service->handlePaymentSuccess($payment->payment_id, 'ext_1');
        $this->assertSame(9.99, (float) $user->fresh()->payment_total_amount);

        // 网关重复通知：不再累计、不重复续期
        $service->handlePaymentSuccess($payment->payment_id, 'ext_2');

        $this->assertSame(9.99, (float) $user->fresh()->payment_total_amount);
        $this->assertSame(1, $payment->fresh()->status);
    }

    public function test_payment_success_activates_purchased_plan_snapshot(): void
    {
        $user = $this->makeUser('free');
        $payment = $this->makePaidPendingPayment($user, 'pro');

        // 下单后、回调前用户切换了当前套餐（模拟等待支付期间变更）
        $user->update(['plan_id' => 'trial']);
        $this->makePlan('trial');

        app(PaymentService::class)->handlePaymentSuccess($payment->payment_id, 'ext_1');

        // 激活的必须是本次购买的套餐（payments.plan_id 快照），而非用户当前套餐
        $this->assertSame('pro', $user->fresh()->plan_id);
    }

    public function test_create_order_persists_plan_id(): void
    {
        $user = $this->makeUser('free');
        $plan = $this->makePlan('pro');
        $user->update(['payment_currency' => 'USD']);

        $order = app(PaymentService::class)->createOrder($user, $plan, 'stripe', 'monthly');

        $this->assertDatabaseHas('payments', [
            'payment_id' => $order['payment_id'],
            'plan_id' => 'pro',
        ]);
    }

    /* ---------------- IDOR ---------------- */

    public function test_account_payments_page_renders_with_plan_relation(): void
    {
        // 回归：payments.plan_id 缺列时 with('plan') 无关系，有支付记录的用户访问 /account-payments 必现 500
        $user = $this->makeUser();
        $payment = $this->makePaidPendingPayment($user);
        $payment->update(['status' => 1]);

        $this->actingAs($user)->get('/account-payments')->assertOk();
    }

    public function test_upload_proof_rejects_foreign_payment(): void
    {
        $owner = $this->makeUser();
        $payment = $this->makePaidPendingPayment($owner);
        $payment->update(['payment_processor' => 'offline']);

        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        $this->actingAs($intruder)
            ->post("/payments/{$payment->payment_id}/proof", ['proof' => $file])
            ->assertForbidden();

        $this->assertEmpty($payment->fresh()->billing['proof_path'] ?? null);
    }
}

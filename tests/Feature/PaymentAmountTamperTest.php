<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 安全审计周期 #13（P3 清偿）：网关金额/币种防篡改校验
 *
 * 缺陷：8 条「handlePaymentSuccess 直接入账」路径（paddle 经典/Billing、
 * lemonsqueezy、yookassa、crypto.com、revolut、razorpay、paystack）只验签
 * + payment_id 关联，不校验回调金额/币种 —— 签名只能证明「通知来自网关」，
 * 不能证明「网关结算金额 == 本地订单金额」。低价支付嫁接本地高价订单
 * 即可全额入账（wechatpay/alipay 此前已有内联校验，本轮统一收口）。
 *
 * 修复语义：PaymentService::assertAmountMatches + majorUnits（ISO 4217
 * 零小数币种表）；金额不匹配/币种不匹配/字段缺失 → 不入账 + warning 日志。
 */
class PaymentAmountTamperTest extends TestCase
{
    use RefreshDatabase;

    private const ORDER_AMOUNT = 9.99; // USD

    private User $user;

    private Payment $payment;

    private string $paddlePrivateKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paddle.webhook_secret', 'paddle_billing_secret');
        config()->set('services.lemonsqueezy.webhook_secret', 'ls_secret');
        config()->set('services.revolut.webhook_secret', 'rev_secret');
        config()->set('services.cryptocom.webhook_secret', 'crypto_secret');
        config()->set('services.razorpay.webhook_secret', 'rzp_secret');
        config()->set('services.paystack.secret_key', 'psk_secret');
        config()->set('services.yookassa.shop_id', 'shop_1');
        config()->set('services.yookassa.secret_key', 'yoo_secret');

        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $this->paddlePrivateKey);
        config()->set('services.paddle.public_key', openssl_pkey_get_details($res)['key']);

        $this->user = User::create([
            'name' => 'Tamper Tester', 'email' => 'tamper@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'pro', 'name' => 'Pro', 'order' => 1,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $this->payment = Payment::create([
            'user_id' => $this->user->user_id,
            'name' => $this->user->name, 'email' => $this->user->email,
            'plan_id' => 'pro', 'payment_processor' => 'paddle',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => self::ORDER_AMOUNT,
            'currency' => 'USD', 'datetime' => now(),
        ]);
    }

    /* ---------------- Razorpay（amount：派萨） ---------------- */

    #[Test]
    public function razorpay_captures_with_matching_amount(): void
    {
        $this->postRazorpay(999, 'USD')->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function razorpay_rejects_underpaid_amount(): void
    {
        $this->postRazorpay(100, 'USD')->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    private function postRazorpay(int $minorAmount, string $currency)
    {
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_ext_1', 'amount' => $minorAmount, 'currency' => $currency,
                'notes' => ['payment_id' => (string) $this->payment->payment_id],
            ]]],
        ];

        return $this->postJson('/webhooks/razorpay', $payload, [
            'X-Razorpay-Signature' => hash_hmac('sha256', json_encode($payload), 'rzp_secret'),
        ]);
    }

    /* ---------------- Paystack（amount：分/派萨） ---------------- */

    #[Test]
    public function paystack_charges_with_matching_amount(): void
    {
        $this->postPaystack(999, 'USD')->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function paystack_rejects_underpaid_amount(): void
    {
        $this->postPaystack(100, 'USD')->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function paystack_rejects_currency_mismatch(): void
    {
        $this->postPaystack(999, 'NGN')->assertOk(); // 金额数值一致但币种不同

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    private function postPaystack(int $minorAmount, string $currency)
    {
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'id' => 'ps_ext_1', 'amount' => $minorAmount, 'currency' => $currency,
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
            ],
        ];

        return $this->postJson('/webhooks/paystack', $payload, [
            'x-paystack-signature' => hash_hmac('sha512', json_encode($payload), 'psk_secret'),
        ]);
    }

    /* ---------------- Paddle Billing（totals.total：分） ---------------- */

    #[Test]
    public function paddle_billing_completes_with_matching_amount(): void
    {
        $payload = [
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_1',
                'custom_data' => ['payment_id' => (string) $this->payment->payment_id],
                'attributes' => ['currency_code' => 'USD', 'totals' => ['total' => 999]],
            ],
        ];

        $this->postJson('/webhooks/paddle-billing', $payload, [
            'Signature' => hash_hmac('sha256', json_encode($payload), 'paddle_billing_secret'),
        ])->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function paddle_billing_rejects_underpaid_amount(): void
    {
        $payload = [
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_1',
                'custom_data' => ['payment_id' => (string) $this->payment->payment_id],
                'attributes' => ['currency_code' => 'USD', 'totals' => ['total' => 100]],
            ],
        ];

        $this->postJson('/webhooks/paddle-billing', $payload, [
            'Signature' => hash_hmac('sha256', json_encode($payload), 'paddle_billing_secret'),
        ])->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    /* ---------------- LemonSqueezy（total：美分） ---------------- */

    #[Test]
    public function lemonsqueezy_creates_order_with_matching_amount(): void
    {
        $payload = [
            'meta' => ['event_name' => 'order_created'],
            'data' => [
                'id' => 'ls_1',
                'attributes' => [
                    'total' => 999, 'currency' => 'USD',
                    'custom_data' => ['payment_id' => (string) $this->payment->payment_id],
                ],
            ],
        ];

        $this->postJson('/webhooks/lemonsqueezy', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'ls_secret'),
        ])->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function lemonsqueezy_rejects_underpaid_amount(): void
    {
        $payload = [
            'meta' => ['event_name' => 'order_created'],
            'data' => [
                'id' => 'ls_1',
                'attributes' => [
                    'total' => 100, 'currency' => 'USD',
                    'custom_data' => ['payment_id' => (string) $this->payment->payment_id],
                ],
            ],
        ];

        $this->postJson('/webhooks/lemonsqueezy', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'ls_secret'),
        ])->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    /* ---------------- Revolut（total_amount：分） ---------------- */

    #[Test]
    public function revolut_completes_with_matching_amount(): void
    {
        $payload = [
            'event' => 'ORDER_COMPLETED',
            'data' => [
                'id' => 'ro_1', 'total_amount' => 999, 'currency' => 'USD',
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
            ],
        ];

        $this->postJson('/webhooks/revolut', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'rev_secret'),
        ])->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function revolut_rejects_underpaid_amount(): void
    {
        $payload = [
            'event' => 'ORDER_COMPLETED',
            'data' => [
                'id' => 'ro_1', 'total_amount' => 100, 'currency' => 'USD',
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
            ],
        ];

        $this->postJson('/webhooks/revolut', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'rev_secret'),
        ])->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    /* ---------------- Crypto.com（amount：分） ---------------- */

    #[Test]
    public function crypto_completes_with_matching_amount(): void
    {
        $payload = [
            'type' => 'payment.created',
            'object' => [
                'id' => 'cc_1', 'amount' => 999, 'currency' => 'USD',
                'status' => 'completed',
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
            ],
        ];

        $this->postJson('/webhooks/crypto', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'crypto_secret'),
        ])->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function crypto_rejects_underpaid_amount(): void
    {
        $payload = [
            'type' => 'payment.created',
            'object' => [
                'id' => 'cc_1', 'amount' => 100, 'currency' => 'USD',
                'status' => 'completed',
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
            ],
        ];

        $this->postJson('/webhooks/crypto', $payload, [
            'X-Signature' => hash_hmac('sha256', json_encode($payload), 'crypto_secret'),
        ])->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    /* ---------------- Paddle 经典（sale_gross：主单位） ---------------- */

    #[Test]
    public function paddle_classic_succeeds_with_matching_amount(): void
    {
        $this->postPaddleClassic('9.99', 'USD')->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function paddle_classic_rejects_underpaid_amount(): void
    {
        $this->postPaddleClassic('1.00', 'USD')->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    private function postPaddleClassic(string $gross, string $currency)
    {
        $payload = [
            'alert_name' => 'payment_succeeded',
            'order_id' => 'ord_1',
            'sale_gross' => $gross,
            'currency' => $currency,
            'passthrough' => json_encode(['payment_id' => (string) $this->payment->payment_id]),
        ];

        ksort($payload);
        openssl_sign(http_build_query($payload), $signature, $this->paddlePrivateKey, OPENSSL_ALGO_SHA256);
        $payload['p_signature'] = base64_encode($signature);

        return $this->postJson('/webhooks/paddle', $payload);
    }

    /* ---------------- YooKassa（回查 amount.value：主单位） ---------------- */

    #[Test]
    public function yookassa_verifies_with_matching_amount(): void
    {
        $this->fakeYookassaLookup('9.99', 'USD');

        $this->postJson('/webhooks/yookassa', [
            'event' => 'payment.succeeded',
            'object' => ['id' => 'yoo_1', 'metadata' => ['payment_id' => (string) $this->payment->payment_id]],
        ])->assertOk();

        $this->assertSame(1, (int) $this->payment->fresh()->status);
    }

    #[Test]
    public function yookassa_rejects_underpaid_amount(): void
    {
        $this->fakeYookassaLookup('1.00', 'USD');

        $this->postJson('/webhooks/yookassa', [
            'event' => 'payment.succeeded',
            'object' => ['id' => 'yoo_1', 'metadata' => ['payment_id' => (string) $this->payment->payment_id]],
        ])->assertOk();

        $this->assertSame(0, (int) $this->payment->fresh()->status);
    }

    private function fakeYookassaLookup(string $value, string $currency): void
    {
        Http::fake(function () use ($value, $currency) {
            return Http::response([
                'status' => 'succeeded',
                'metadata' => ['payment_id' => (string) $this->payment->payment_id],
                'amount' => ['value' => $value, 'currency' => $currency],
            ]);
        });
    }

    /* ---------------- helper 单元 ---------------- */

        #[Test]
    public function major_units_handles_zero_decimal_currencies(): void
    {
        $this->assertSame(1000.0, PaymentService::majorUnits(1000, 'JPY'));
        $this->assertSame(1000.0, PaymentService::majorUnits(1000, 'jpy'));
        $this->assertSame(9.99, PaymentService::majorUnits(999, 'USD'));
        $this->assertNull(PaymentService::majorUnits(null, 'USD'));
        $this->assertNull(PaymentService::majorUnits('abc', 'USD'));
    }

    /* ------------------------------------------------------------------ */
    /*  安全审计周期 #19：Stripe / PayPal / Mollie 金额防篡改校验           */
    /* ------------------------------------------------------------------ */

    #[Test]
        public function stripe_rejects_tampered_amount(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_c19');

        $user = User::create([
            'name' => 'Stripe Tamper', 'email' => 'st@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'c19_stripe', 'name' => 'C19 Stripe', 'order' => 99,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'c19_stripe', 'payment_processor' => 'stripe',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'USD', 'datetime' => now(),
        ]);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_tamper', 'payment_intent' => 'pi_tamper',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
                'amount_total' => 100, 'currency' => 'usd',
            ]],
        ];
        $body = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_c19');

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => "t={$timestamp},v1={$signature}",
        ]), $body)->assertOk();

        $this->assertSame(0, (int) $payment->fresh()->status);
    }

    #[Test]
    public function stripe_rejects_currency_mismatch(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_c19');

        $user = User::create([
            'name' => 'Stripe Curr', 'email' => 'stcurr@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'pro2', 'name' => 'Pro2', 'order' => 2,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'pro2', 'payment_processor' => 'stripe',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'USD', 'datetime' => now(),
        ]);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_curr', 'payment_intent' => 'pi_curr',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
                'amount_total' => 999, 'currency' => 'eur',
            ]],
        ];
        $body = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_c19');

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => "t={$timestamp},v1={$signature}",
        ]), $body)->assertOk();

                $this->assertSame(0, (int) $payment->fresh()->status);
    }

    /* ---------------- Mollie（amount.value：主单位，API 回查） ---------------- */

    #[Test]
    public function mollie_verify_gateway_amount_rejects_underpayment(): void
    {
        $user = User::create([
            'name' => 'Mollie Tamper', 'email' => 'mollie@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'mollie_pro', 'name' => 'Mollie Pro', 'order' => 3,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'EUR',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'mollie_pro', 'payment_processor' => 'mollie',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'EUR', 'datetime' => now(),
        ]);

        $service = app(PaymentService::class);
        $this->assertFalse(
            $service->verifyGatewayAmount($payment->payment_id, 1.00, 'EUR', 'mollie')
        );
        $this->assertTrue(
            $service->verifyGatewayAmount($payment->payment_id, 9.99, 'EUR', 'mollie')
        );
    }

    #[Test]
    public function mollie_rejects_currency_mismatch(): void
    {
        $user = User::create([
            'name' => 'Mollie Curr', 'email' => 'mollcurr@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'mollie2', 'name' => 'M2', 'order' => 4,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'EUR',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'mollie2', 'payment_processor' => 'mollie',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'EUR', 'datetime' => now(),
        ]);

                $service = app(PaymentService::class);
        $this->assertFalse(
            $service->verifyGatewayAmount($payment->payment_id, 9.99, 'USD', 'mollie')
        );
    }

    /* ---------------- PayPal（capture 回查 amount.value：主单位） ---------------- */

    #[Test]
    public function paypal_verify_gateway_amount_rejects_underpayment(): void
    {
        $user = User::create([
            'name' => 'PayPal Tamper', 'email' => 'pp@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'pp_pro', 'name' => 'PP Pro', 'order' => 5,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'pp_pro', 'payment_processor' => 'paypal',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'USD', 'datetime' => now(),
        ]);

        $service = app(PaymentService::class);
        $this->assertFalse(
            $service->verifyGatewayAmount($payment->payment_id, 1.00, 'USD', 'paypal')
        );
        $this->assertTrue(
            $service->verifyGatewayAmount($payment->payment_id, 9.99, 'USD', 'paypal')
        );
    }

    #[Test]
    public function paypal_rejects_currency_mismatch(): void
    {
        $user = User::create([
            'name' => 'PayPal Curr', 'email' => 'ppcurr@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'pp2', 'name' => 'PP2', 'order' => 6,
            'monthly_price' => 9.99, 'yearly_price' => 99.99, 'currency' => 'USD',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'pp2', 'payment_processor' => 'paypal',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 9.99,
            'currency' => 'USD', 'datetime' => now(),
        ]);

        $service = app(PaymentService::class);
        $this->assertFalse(
            $service->verifyGatewayAmount($payment->payment_id, 9.99, 'EUR', 'paypal')
        );
    }

    /* ---------------- 零小数币种（JPY）Stripe 金额校验 ---------------- */

    #[Test]
    public function stripe_jpy_order_rejects_tampered_amount(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_jpy');

        $user = User::create([
            'name' => 'JPY Tamper', 'email' => 'jpy@example.com',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free',
        ]);
        Plan::create([
            'plan_id' => 'jpy_pro', 'name' => 'JPY Pro', 'order' => 7,
            'monthly_price' => 1000, 'yearly_price' => 10000, 'currency' => 'JPY',
        ]);
        $payment = Payment::create([
            'user_id' => $user->user_id, 'name' => $user->name, 'email' => $user->email,
            'plan_id' => 'jpy_pro', 'payment_processor' => 'stripe',
            'type' => 'one_time', 'frequency' => 'monthly',
            'status' => 0, 'total_amount' => 1000,
            'currency' => 'JPY', 'datetime' => now(),
        ]);

        $payload = [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_jpy', 'payment_intent' => 'pi_jpy',
                'metadata' => ['payment_id' => (string) $payment->payment_id],
                'amount_total' => 100, 'currency' => 'jpy',
            ]],
        ];
        $body = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_jpy');

        $this->call('POST', '/webhooks/stripe', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json',
            'Stripe-Signature' => "t={$timestamp},v1={$signature}",
        ]), $body)->assertOk();

        $this->assertSame(0, (int) $payment->fresh()->status);
    }
}


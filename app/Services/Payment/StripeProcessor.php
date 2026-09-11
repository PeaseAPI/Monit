<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Support\Typed;
use App\Support\WebhookSignature;
use Illuminate\Http\Request;

/**
 * Stripe 支付处理器
 * 规格书 §11：Stripe 一次性 + 订阅
 */
class StripeProcessor
{
    protected ?string $secretKey;

    protected ?string $publishableKey;

    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = Typed::stringOrNull(config('services.stripe.secret'));
        $this->publishableKey = Typed::stringOrNull(config('services.stripe.key'));
        $this->webhookSecret = Typed::stringOrNull(config('services.stripe.webhook_secret'));
    }

    /**
     * 是否已配置
     */
    public function isConfigured(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->publishableKey);
    }

    /**
     * 创建 Stripe Checkout Session
     *
     * @return array<string, mixed>
     */
    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Stripe not configured'];
        }

        $payload = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'product_data' => [
                        'name' => 'Monit Plan',
                    ],
                    'unit_amount' => (int) ((float) ($payment->total_amount ?? 0) * 100), // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => $payment->type === 'recurring' ? 'subscription' : 'payment',
            'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'payment_id' => $payment->payment_id,
                'user_id' => $payment->user_id,
            ],
        ];

        return [
            'processor' => 'stripe',
            'publishable_key' => $this->publishableKey,
            'session_payload' => $payload,
        ];
    }

    /**
     * 验证 Webhook 签名（Stripe 官方 HMAC-SHA256 方案，fail-closed）
     *
     * Stripe-Signature: t=<timestamp>,v1=<hmac>
     * 期望值 = HMAC-SHA256(webhook_secret, "{t}.{rawBody}")，恒时比较 + 5 分钟重放容差。
     * 未配置 webhook_secret、缺头、签名不符、时间戳超差 → 一律拒绝。
     */
    public function verifyWebhook(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }

        return WebhookSignature::verifyStripeSignature(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            $this->webhookSecret
        );
    }

    /**
     * 解析 Webhook 事件
     *
     * @return array<string, mixed>
     */
    public function parseWebhookEvent(Request $request): array
    {
        $payload = Typed::arr($request->input());
        $type = $payload['type'] ?? '';

        return match ($type) {
            'checkout.session.completed' => [
                'event' => 'payment_success',
                'external_id' => data_get($payload, 'data.object.payment_intent') ?? data_get($payload, 'data.object.id'),
                'subscription_id' => data_get($payload, 'data.object.subscription'),
                'payment_id' => data_get($payload, 'data.object.metadata.payment_id'),
                // 金额防篡改（安全审计周期 #19）：amount_total 为最小单位（分），
                // 由 PaymentController 换算主单位后与本地订单比对
                'amount_total' => data_get($payload, 'data.object.amount_total'),
                'currency' => strtoupper(Typed::string(data_get($payload, 'data.object.currency'))),
            ],
            'customer.subscription.deleted' => [
                'event' => 'subscription_cancelled',
                'subscription_id' => data_get($payload, 'data.object.id'),
            ],
            'payment_intent.payment_failed' => [
                'event' => 'payment_failure',
                'external_id' => data_get($payload, 'data.object.id'),
                'payment_id' => data_get($payload, 'data.object.metadata.payment_id'),
                'reason' => data_get($payload, 'data.object.last_payment_error.message'),
            ],
            default => [
                'event' => 'unknown',
                'type' => $type,
            ],
        };
    }
}

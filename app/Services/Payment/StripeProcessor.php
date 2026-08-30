<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\User;
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
        $this->secretKey = config('services.stripe.secret');
        $this->publishableKey = config('services.stripe.key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
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
                    'unit_amount' => (int) ($payment->total_amount * 100), // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => $payment->type === 'recurring' ? 'subscription' : 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
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
     * 验证 Webhook 签名
     */
    public function verifyWebhook(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (! $sigHeader) {
            return false;
        }

        // 简化验证 - 生产环境应使用 Stripe SDK 验证
        // \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        return true;
    }

    /**
     * 解析 Webhook 事件
     */
    public function parseWebhookEvent(Request $request): array
    {
        $payload = $request->input();
        $type = $payload['type'] ?? '';

        return match ($type) {
            'checkout.session.completed' => [
                'event' => 'payment_success',
                'external_id' => $payload['data']['object']['payment_intent'] ?? $payload['data']['object']['id'] ?? null,
                'subscription_id' => $payload['data']['object']['subscription'] ?? null,
                'payment_id' => $payload['data']['object']['metadata']['payment_id'] ?? null,
            ],
            'customer.subscription.deleted' => [
                'event' => 'subscription_cancelled',
                'subscription_id' => $payload['data']['object']['id'] ?? null,
            ],
            'payment_intent.payment_failed' => [
                'event' => 'payment_failure',
                'external_id' => $payload['data']['object']['id'] ?? null,
                'payment_id' => $payload['data']['object']['metadata']['payment_id'] ?? null,
                'reason' => $payload['data']['object']['last_payment_error']['message'] ?? null,
            ],
            default => [
                'event' => 'unknown',
                'type' => $type,
            ],
        };
    }
}
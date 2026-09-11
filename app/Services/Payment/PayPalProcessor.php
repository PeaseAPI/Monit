<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Support\Typed;
use App\Support\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * PayPal 支付处理器
 * 规格书 §11：PayPal 一次性 + 订阅
 */
class PayPalProcessor
{
    protected ?string $clientId;

    protected ?string $clientSecret;

    protected string $baseUrl;

    public function __construct()
    {
        $this->clientId = Typed::stringOrNull(config('services.paypal.client_id'));
        $this->clientSecret = Typed::stringOrNull(config('services.paypal.client_secret'));
        $this->baseUrl = config('services.paypal.sandbox', true)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * 是否已配置
     */
    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    /**
     * 获取 Access Token
     */
    public function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // 简化实现 - 生产环境应缓存 token
        try {
            $response = Http::withBasicAuth((string) $this->clientId, (string) $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            $token = $response->json('access_token');

            return is_string($token) ? $token : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 创建 PayPal 订单
     *
     * @return array<string, mixed>
     */
    public function createOrder(Payment $payment, string $returnUrl, string $cancelUrl): array
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return ['error' => 'PayPal not configured or auth failed'];
        }

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $payment->payment_id,
                'amount' => [
                    'currency_code' => $payment->currency,
                    'value' => number_format((float) $payment->total_amount, 2, '.', ''),
                ],
                'custom_id' => (string) $payment->payment_id,
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'Monit',
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders", $orderData);

            $data = Typed::arr($response->json());
            $links = $data['links'] ?? [];

            $approve = collect(Typed::arr($links))->firstWhere('rel', 'approve');

            return [
                'processor' => 'paypal',
                'order_id' => Typed::stringOrNull($data['id'] ?? null),
                'approve_url' => is_array($approve) ? Typed::stringOrNull($approve['href'] ?? null) : null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 捕获 PayPal 订单
     *
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();
        if (! $accessToken) {
            return ['error' => 'PayPal auth failed'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            $data = Typed::arr($response->json());

            return [
                'captured' => ($data['status'] ?? '') === 'COMPLETED',
                'external_id' => Typed::stringOrNull($data['id'] ?? null) ?? $orderId,
                'payment_id' => Typed::stringOrNull(data_get($data, 'purchase_units.0.custom_id')),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 验证 Webhook 签名（PayPal 官方 verify-webhook-signature API，fail-closed）
     *
     * 将 PayPal 传输头 + webhook_id 提交 PayPal 验签接口，
     * verification_status === 'SUCCESS' 才放行；
     * 未配置 webhook_id / 取 token 失败 / 网络异常 → 一律拒绝。
     */
    public function verifyWebhook(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        if (! is_string($webhookId) || $webhookId === '' || ! $this->isConfigured()) {
            return false;
        }

        $accessToken = $this->getAccessToken();

        return WebhookSignature::verifyPayPalSignature(
            $request,
            $this->baseUrl,
            $accessToken,
            $webhookId
        );
    }
}

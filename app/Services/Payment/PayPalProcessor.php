<?php

namespace App\Services\Payment;

use App\Models\Payment;
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
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
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
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            return $response->json('access_token');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 创建 PayPal 订单
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
                    'value' => number_format($payment->total_amount, 2, '.', ''),
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

            $data = $response->json();

            return [
                'processor' => 'paypal',
                'order_id' => $data['id'] ?? null,
                'approve_url' => collect($data['links'] ?? [])
                    ->firstWhere('rel', 'approve')['href'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 捕获 PayPal 订单
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

            $data = $response->json();

            return [
                'captured' => ($data['status'] ?? '') === 'COMPLETED',
                'external_id' => $data['id'] ?? $orderId,
                'payment_id' => $data['purchase_units'][0]['custom_id'] ?? null,
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

        if (empty($webhookId) || ! $this->isConfigured()) {
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

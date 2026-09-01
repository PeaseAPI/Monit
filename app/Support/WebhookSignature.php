<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 支付回调验签支撑（安全基座）
 *
 * 统一原则 —— fail-closed：
 *   任何回调处理器在「密钥未配置」或「签名不匹配」时必须拒绝入账，
 *   绝不允许以「未配置/简化实现」为由放行（否则等于开放伪造支付入口）。
 */
class WebhookSignature
{
    /** Stripe / Paddle Billing 等时间戳容差（秒） */
    public const TIMESTAMP_TOLERANCE = 300;

    /**
     * Stripe 官方签名验证
     * 头格式：Stripe-Signature: t=1712345678,v1=abcdef...,v1=...（可多个 v1）
     * 期望值：HMAC-SHA256(secret, "{t}.{rawBody}")，与任一 v1 恒时比较
     */
    public static function verifyStripeSignature(string $rawBody, ?string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === null || $signatureHeader === '' || $secret === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            $kv = explode('=', trim($segment), 2);
            if (count($kv) === 2) {
                $parts[trim($kv[0])][] = trim($kv[1]);
            }
        }

        $timestamp = $parts['t'][0] ?? null;
        if ($timestamp === null || ! ctype_digit($timestamp)) {
            return false;
        }

        // 重放保护：时间戳超出容差即拒绝
        if (abs(time() - (int) $timestamp) > self::TIMESTAMP_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        foreach ($parts['v1'] ?? [] as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * PayPal 官方验证：POST /v1/notifications/verify-webhook-signature
     * 需要配置 webhook_id；未配置 / 网络失败一律拒绝（fail-closed）
     */
    public static function verifyPayPalSignature(
        Request $request,
        string $baseUrl,
        ?string $accessToken,
        ?string $webhookId,
    ): bool {
        if (! $accessToken || empty($webhookId)) {
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->acceptJson()
                ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                    'transmission_id' => $request->header('paypal-transmission-id'),
                    'transmission_time' => $request->header('paypal-transmission-time'),
                    'cert_url' => $request->header('paypal-cert-url'),
                    'auth_algo' => $request->header('paypal-auth-algo'),
                    'transmission_sig' => $request->header('paypal-transmission-sig'),
                    'webhook_id' => $webhookId,
                    'webhook_event' => $request->input(),
                ]);

            return $response->ok() && ($response->json('verification_status') === 'SUCCESS');
        } catch (\Throwable $e) {
            Log::warning('webhook.paypal_verify_failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Paddle 经典（alert_name 体系）官方 RSA 验签：
     * 对除 p_signature 外的全部字段 ksort 后 http_build_query，
     * 用商户公钥 openssl_verify(SHA256) 校验 base64 签名
     */
    public static function verifyPaddleClassic(array $payload, string $publicKey): bool
    {
        $signature = base64_decode((string) ($payload['p_signature'] ?? ''), true);
        unset($payload['p_signature']);

        if ($signature === false || $signature === '') {
            return false;
        }

        ksort($payload);
        $signedData = http_build_query($payload);

        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            return false;
        }

        $result = openssl_verify($signedData, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);

        return $result === 1;
    }

    /**
     * 通用 HMAC 守门：hash_hmac('sha256', rawBody, secret) 与指定头恒时比较
     *
     * 适用于官方采用「HMAC(原始请求体)」方案的网关（如 Paddle Billing 的 Signature 头），
     * 以及作为无本地验签机制网关的防御性默认（配合网关侧代理注入签名头）。
     * secret 为空 → 拒绝（fail-closed）。
     */
    public static function verifyHmacHeader(Request $request, ?string $secret, string $header = 'X-Signature'): bool
    {
        if (empty($secret)) {
            return false;
        }

        $provided = (string) $request->header($header, '');

        // 兼容 hex 与 base64 两种常见签名编码
        $hex = hash_hmac('sha256', $request->getContent(), $secret);
        $b64 = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return $provided !== '' && (hash_equals($hex, $provided) || hash_equals($b64, $provided));
    }

    /**
     * YooKassa 回查：通知本身无签名，官方要求回查支付状态
     * GET /v3/payments/{id}（Basic auth = shopId:secretKey）
     * 返回回查载荷（调用方须比对 metadata.payment_id 防止「真交易 id + 伪造 metadata」嫁接）
     */
    public static function fetchYooKassaPayment(string $paymentId, ?string $shopId, ?string $secretKey): ?array
    {
        if (empty($shopId) || empty($secretKey) || $paymentId === '') {
            return null;
        }

        try {
            $response = Http::withBasicAuth($shopId, $secretKey)
                ->timeout(10)
                ->acceptJson()
                ->get("https://api.yookassa.ru/v3/payments/{$paymentId}");

            if ($response->ok() && ($response->json('status') === 'succeeded')) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('webhook.yookassa_verify_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * 回调 URL 安全校验：仅允许 http/https（拒绝 file://、ftp:// 等 SSRF 向量）
     */
    /**
     * 出站 Webhook 签名（webhooks.webhooks_secret_key）
     * HMAC-SHA256(json(body))，hex 输出，接收方可用同算法验证
     */
    public static function sign(array $body, string $secret): string
    {
        return hash_hmac('sha256', json_encode($body, JSON_UNESCAPED_UNICODE) ?: '', $secret);
    }

    public static function isSafeHttpUrl(string $url): bool
    {
        $parsed = parse_url(trim($url));

        return $parsed !== false
            && isset($parsed['scheme'], $parsed['host'])
            && in_array(strtolower($parsed['scheme']), ['http', 'https'], true);
    }
}

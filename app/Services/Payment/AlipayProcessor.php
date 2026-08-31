<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Http\Response;

/**
 * Alipay 支付处理器（规格书 §11：中国，一次性）
 * 电脑网站支付 alipay.trade.page.pay（表单跳转），异步 notify 验签 RSA2。
 */
class AlipayProcessor
{
    protected const GATEWAY = 'https://openapi.alipay.com/gateway.do';

    public function isConfigured(): bool
    {
        return (bool) (config('services.alipay.app_id') && config('services.alipay.private_key'));
    }

    /**
     * 构建跳转支付表单（自提交 HTML）
     */
    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        try {
            $biz = [
                'out_trade_no' => 'monit_'.$payment->payment_id.'_'.now()->format('His'),
                'total_amount' => number_format((float) $payment->total_amount, 2, '.', ''),
                'subject' => mb_substr('Monit Plan '.$payment->payment_id, 0, 256),
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
                'passback_params' => urlencode(json_encode(['payment_id' => $payment->payment_id])),
            ];

            $params = [
                'app_id' => config('services.alipay.app_id'),
                'method' => 'alipay.trade.page.pay',
                'format' => 'JSON',
                'charset' => 'utf-8',
                'sign_type' => 'RSA2',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'version' => '1.0',
                'notify_url' => route('webhooks.alipay'),
                'return_url' => $successUrl,
                'biz_content' => json_encode($biz, JSON_UNESCAPED_UNICODE),
            ];

            $params['sign'] = $this->sign($params);

            $fields = '';
            foreach ($params as $key => $value) {
                $fields .= '<input type="hidden" name="'.e($key).'" value="'.e($value).'">';
            }

            return [
                'redirect_html' => '<form id="alipay-submit" method="POST" action="'.static::GATEWAY.'?charset=utf-8">'.$fields.'</form><script>document.getElementById("alipay-submit").submit();</script>',
                'out_trade_no' => $biz['out_trade_no'],
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 验证异步通知签名（RSA2，支付宝公钥）
     */
    public function verifyNotify(array $data): bool
    {
        if (empty($data['sign']) || empty($data['sign_type'])) {
            return false;
        }

        $sign = (string) $data['sign'];
        unset($data['sign'], $data['sign_type']);

        ksort($data);

        $parts = [];
        foreach ($data as $key => $value) {
            if ($value !== '' && $value !== null) {
                $parts[] = $key.'='.$value;
            }
        }
        $content = implode('&', $parts);

        $publicKey = $this->normalizePublicKey((string) config('services.alipay.alipay_public_key'));

        return (bool) openssl_verify($content, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
    }

    /**
     * 通知应答（支付宝要求纯字符串 success）
     */
    public function successResponse(): Response
    {
        return response('success', 200)->header('Content-Type', 'text/plain');
    }

    protected function sign(array $params): string
    {
        unset($params['sign']);

        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $parts[] = $key.'='.$value;
            }
        }
        $content = implode('&', $parts);

        $privateKey = $this->normalizePrivateKey((string) config('services.alipay.private_key'));

        openssl_sign($content, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    protected function normalizePrivateKey(string $key): string
    {
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        return "-----BEGIN RSA PRIVATE KEY-----\n".chunk_split($key, 64, "\n")."-----END RSA PRIVATE KEY-----\n";
    }

    protected function normalizePublicKey(string $key): string
    {
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split($key, 64, "\n")."-----END PUBLIC KEY-----\n";
    }
}

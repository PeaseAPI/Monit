<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;

/**
 * WeChat Pay 支付处理器（规格书 §11：中国，一次性）
 * Native 扫码支付（API v2 统一下单，MD5 签名），webhook 为 XML 回调。
 */
class WeChatPayProcessor
{
    protected const UNIFIED_ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    public function isConfigured(): bool
    {
        return (bool) (config('services.wechat_pay.mch_id') && config('services.wechat_pay.app_id') && config('services.wechat_pay.api_key'));
    }

    /**
     * 生成 Native 支付二维码链接（code_url → 前端渲染二维码）
     */
    public function createOrder(Payment $payment, string $successUrl, string $cancelUrl): array
    {
        try {
            $params = [
                'appid' => config('services.wechat_pay.app_id'),
                'mch_id' => config('services.wechat_pay.mch_id'),
                'nonce_str' => bin2hex(random_bytes(16)),
                'body' => mb_substr('Monit Plan ' . $payment->payment_id, 0, 127),
                'out_trade_no' => 'monit_' . $payment->payment_id . '_' . now()->format('His'),
                'total_fee' => (int) round(((float) $payment->total_amount) * 100), // 单位：分
                'spbill_create_ip' => request()->ip() ?: '127.0.0.1',
                'notify_url' => route('webhooks.wechatpay'),
                'trade_type' => 'NATIVE',
                'product_id' => (string) $payment->payment_id,
                'attach' => json_encode(['payment_id' => $payment->payment_id]),
            ];

            $params['sign'] = $this->sign($params);

            $response = Http::asXml($params)->post(static::UNIFIED_ORDER_URL);
            $xml = simplexml_load_string((string) $response->body());

            if ($xml !== false && (string) $xml->return_code === 'SUCCESS' && (string) $xml->result_code === 'SUCCESS') {
                return [
                    'code_url' => (string) $xml->code_url,
                    'out_trade_no' => $params['out_trade_no'],
                    'prepay_id' => (string) $xml->prepay_id,
                ];
            }

            return ['error' => (string) ($xml->err_code_des ?? $xml->return_msg ?? 'Unknown error')];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 验证回调签名（API v2 MD5）
     */
    public function verifyCallback(array $data): bool
    {
        if (empty($data['sign'])) {
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);

        return hash_equals(strtoupper($this->sign($data)), strtoupper((string) $sign));
    }

    /**
     * 主动查单（回调丢失时对账）
     */
    public function queryOrder(string $outTradeNo): ?array
    {
        try {
            $params = [
                'appid' => config('services.wechat_pay.app_id'),
                'mch_id' => config('services.wechat_pay.mch_id'),
                'out_trade_no' => $outTradeNo,
                'nonce_str' => bin2hex(random_bytes(16)),
            ];
            $params['sign'] = $this->sign($params);

            $response = Http::asXml($params)->post('https://api.mch.weixin.qq.com/pay/orderquery');
            $xml = simplexml_load_string((string) $response->body());

            if ($xml !== false && (string) $xml->return_code === 'SUCCESS' && (string) $xml->trade_state === 'SUCCESS') {
                return [
                    'out_trade_no' => (string) $xml->out_trade_no,
                    'transaction_id' => (string) $xml->transaction_id,
                    'total_fee' => (int) $xml->total_fee,
                ];
            }
        } catch (\Throwable) {
        }

        return null;
    }

    protected function sign(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== '' && $value !== null) {
                $parts[] = $key . '=' . $value;
            }
        }

        $string = implode('&', $parts) . '&key=' . config('services.wechat_pay.api_key');

        return strtoupper(md5($string));
    }
}

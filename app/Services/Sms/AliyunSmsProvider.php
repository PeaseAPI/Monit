<?php

namespace App\Services\Sms;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * 阿里云短信（dysmsapi.aliyuncs.com，RPC API V1 签名，M17 规格书 §12.5）
 *
 * 凭据来源：settings sms.sms_aliyun_* 优先，回落 config/services.php（env：SMS_ALIYUN_*）
 * 模板变量固定为 {"code": "123456"}，对应控制台模板 ${code}
 */
class AliyunSmsProvider
{
    public const ENDPOINT = 'https://dysmsapi.aliyuncs.com/';

    public function __construct(
        protected string $accessKeyId,
        protected string $accessKeySecret,
        protected string $signName,
        protected string $templateCode,
    ) {}

    public static function make(): static
    {
        return new static(
            (string) (Settings::get('sms.sms_aliyun_access_key_id') ?: config('services.sms_aliyun.access_key_id', '')),
            (string) (Settings::get('sms.sms_aliyun_access_key_secret') ?: config('services.sms_aliyun.access_key_secret', '')),
            (string) (Settings::get('sms.sms_aliyun_sign_name') ?: config('services.sms_aliyun.sign_name', '')),
            (string) (Settings::get('sms.sms_aliyun_template_code') ?: config('services.sms_aliyun.template_code', '')),
        );
    }

    /**
     * 发送验证码短信
     *
     * @return array{0: bool, 1: string} [是否成功, 错误信息]
     */
    public function send(string $phone, string $code): array
    {
        if ($this->accessKeyId === '' || $this->accessKeySecret === '' || $this->signName === '' || $this->templateCode === '') {
            return [false, 'aliyun_not_configured'];
        }

        $params = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $phone,
            'RegionId' => 'cn-hangzhou',
            'SignName' => $this->signName,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'TemplateCode' => $this->templateCode,
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => now()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];

        $params['Signature'] = $this->sign($params);

        $response = Http::asForm()->timeout(10)->post(static::ENDPOINT, $params);
        $body = $response->json();

        if ($response->failed() || (($body['Code'] ?? 'OK') !== 'OK')) {
            return [false, (string) ($body['Message'] ?? $body['Code'] ?? 'aliyun_request_failed')];
        }

        return [true, ''];
    }

    /** RPC V1 签名：POST&%2F&percentEncode(canonicalizedQuery) 的 HMAC-SHA1 */
    protected function sign(array $params): string
    {
        unset($params['Signature']);
        ksort($params);

        $canonicalized = implode('&', array_map(
            fn (string $key, string $value) => static::percentEncode($key).'='.static::percentEncode($value),
            array_keys($params),
            $params,
        ));

        $stringToSign = 'POST&'.static::percentEncode('/').'&'.static::percentEncode($canonicalized);

        return base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret.'&', true));
    }

    /** 阿里云 POP 编码：RFC3986（+ → %20、* → %2A、%7E → ~） */
    protected static function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
    }
}

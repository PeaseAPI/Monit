<?php

namespace App\Services\Sms;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * 腾讯云短信（sms.tencentcloudapi.com，TC3-HMAC-SHA256 签名，M17 规格书 §12.5）
 *
 * 凭据来源：settings sms.sms_tencent_* 优先，回落 config/services.php（env：SMS_TENCENT_*）
 * 模板变量固定为 ["123456"]，对应控制台模板 {1} 或 {code}
 */
class TencentSmsProvider
{
    public const ENDPOINT = 'https://sms.tencentcloudapi.com';

    public const ACTION = 'SendSms';

    public const VERSION = '2021-01-11';

    public function __construct(
        protected string $secretId,
        protected string $secretKey,
        protected string $sdkAppId,
        protected string $signName,
        protected string $templateId,
    ) {
    }

    public static function make(): static
    {
        return new static(
            (string) (Settings::get('sms.sms_tencent_secret_id') ?: config('services.sms_tencent.secret_id', '')),
            (string) (Settings::get('sms.sms_tencent_secret_key') ?: config('services.sms_tencent.secret_key', '')),
            (string) (Settings::get('sms.sms_tencent_sdk_app_id') ?: config('services.sms_tencent.sdk_app_id', '')),
            (string) (Settings::get('sms.sms_tencent_sign_name') ?: config('services.sms_tencent.sign_name', '')),
            (string) (Settings::get('sms.sms_tencent_template_id') ?: config('services.sms_tencent.template_id', '')),
        );
    }

    /**
     * 发送验证码短信（国内号默认 +86 前缀）
     *
     * @return array{0: bool, 1: string} [是否成功, 错误信息]
     */
    public function send(string $phone, string $code): array
    {
        if ($this->secretId === '' || $this->secretKey === '' || $this->sdkAppId === '' || $this->signName === '' || $this->templateId === '') {
            return [false, 'tencent_not_configured'];
        }

        $body = json_encode([
            'PhoneNumberSet' => ['+86' . $phone],
            'SmsSdkAppId' => $this->sdkAppId,
            'SignName' => $this->signName,
            'TemplateId' => $this->templateId,
            'TemplateParamSet' => [$code],
        ], JSON_UNESCAPED_UNICODE);

        $headers = $this->signedHeaders($body);

        $response = Http::withHeaders($headers)->timeout(10)
            ->withBody($body, 'application/json; charset=utf-8')
            ->post(static::ENDPOINT);

        $result = $response->json();
        $sendStatus = $result['Response']['SendStatusSet'][0] ?? null;

        if ($response->failed() || isset($result['Response']['Error'])) {
            return [false, (string) ($result['Response']['Error']['Message'] ?? 'tencent_request_failed')];
        }

        if ($sendStatus && (($sendStatus['Code'] ?? '') !== 'Ok')) {
            return [false, (string) ($sendStatus['Message'] ?? $sendStatus['Code'] ?? 'tencent_send_failed')];
        }

        return [true, ''];
    }

    /** TC3-HMAC-SHA256 签名请求头 */
    protected function signedHeaders(string $jsonBody): array
    {
        $host = 'sms.tencentcloudapi.com';
        $timestamp = now()->timestamp;
        $date = gmdate('Y-m-d', $timestamp);

        $contentType = 'application/json; charset=utf-8';
        $actionLower = strtolower(static::ACTION);

        // CanonicalRequest
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\nx-tc-action:{$actionLower}\n";
        $signedHeaders = 'content-type;host;x-tc-action';
        $canonicalRequest = implode("\n", [
            'POST',
            '/',
            '',
            $canonicalHeaders,
            $signedHeaders,
            hash('sha256', $jsonBody),
        ]);

        // StringToSign
        $credentialScope = "{$date}/sms/tc3_request";
        $stringToSign = implode("\n", [
            'TC3-HMAC-SHA256',
            (string) $timestamp,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        // 签名密钥链
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $this->secretKey, true);
        $secretService = hash_hmac('sha256', 'sms', $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        return [
            'Content-Type' => $contentType,
            'Host' => $host,
            'X-TC-Action' => static::ACTION,
            'X-TC-Version' => static::VERSION,
            'X-TC-Timestamp' => (string) $timestamp,
            'Authorization' => sprintf(
                'TC3-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
                $this->secretId,
                $credentialScope,
                $signedHeaders,
                $signature,
            ),
        ];
    }
}

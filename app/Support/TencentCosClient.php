<?php

namespace App\Support;

/**
 * 纯 PHP 腾讯云 COS 客户端（M17 规格书 §14.8 对象存储扩展）
 * 接口与 S3Client 对齐：put / get / delete / exists，返回 [status, body, error]。
 *
 * 签名方式：COS XML API 签名（q-sign-algorithm=sha1）
 *   SignKey      = hex(hmac_sha1(KeyTime, SecretKey))
 *   HttpString   = lower(method)\nUrlPath\nUrlParamList\nHeaderList\n
 *   StringToSign = sha1\nKeyTime\nsha1(HttpString)\n
 *   Signature    = hex(hmac_sha1(StringToSign, SignKey))
 *
 * 地址风格：virtual-hosted —— https://{bucket}.cos.{region}.myqcloud.com/{key}
 * （bucket 名需含 APPID 后缀，如 monit-1250000000）
 */
class TencentCosClient
{
    public function __construct(
        protected string $secretId,
        protected string $secretKey,
        protected string $bucket,
        protected string $region = 'ap-guangzhou',
    ) {}

    /** 对象完整 URL（virtual-hosted style） */
    public function urlFor(string $key): string
    {
        return 'https://'.$this->bucket.'.cos.'.$this->region.'.myqcloud.com/'.ltrim($key, '/');
    }

    public function put(string $key, string $content, string $contentType = 'application/octet-stream'): array
    {
        return $this->request('PUT', $key, $content, ['Content-Type' => $contentType]);
    }

    public function get(string $key): array
    {
        return $this->request('GET', $key);
    }

    public function delete(string $key): array
    {
        return $this->request('DELETE', $key);
    }

    public function exists(string $key): bool
    {
        [$status] = $this->request('HEAD', $key);

        return $status === 200;
    }

    /* ---------------- COS 签名（q-sign-algorithm=sha1） ---------------- */

    protected function request(string $method, string $key, ?string $body = null, array $headers = []): array
    {
        $url = $this->urlFor($key);
        $host = $this->bucket.'.cos.'.$this->region.'.myqcloud.com';
        $contentType = (string) ($headers['Content-Type'] ?? '');

        $startTime = now()->timestamp - 60;
        $endTime = now()->timestamp + 600;
        $keyTime = $startTime.';'.$endTime;

        // 签名头（小写排序）
        $headerPairs = ['host' => $host];
        if ($contentType !== '') {
            $headerPairs['content-type'] = $contentType;
        }
        ksort($headerPairs);

        $headerList = implode('&', array_map(
            fn (string $name, string $value) => $name.'='.static::cosEncode($value),
            array_keys($headerPairs),
            $headerPairs,
        ));
        $signedHeaderNames = implode(';', array_keys($headerPairs));

        $uriPath = '/'.ltrim($key, '/');

        $httpString = strtolower($method)."\n".$uriPath."\n\n".$headerList."\n";
        $stringToSign = implode("\n", ['sha1', $keyTime, sha1($httpString), '']);

        $signKey = hash_hmac('sha1', $keyTime, $this->secretKey);
        $signature = hash_hmac('sha1', $stringToSign, $signKey);

        $authorization = sprintf(
            'q-sign-algorithm=sha1&q-ak=%s&q-sign-time=%s&q-key-time=%s&q-header-list=%s&q-url-param-list=&q-signature=%s',
            $this->secretId,
            $keyTime,
            $keyTime,
            $signedHeaderNames,
            $signature,
        );

        $curlHeaders = ['Authorization: '.$authorization];
        foreach ($headerPairs as $h => $v) {
            if ($h !== 'host') {
                $curlHeaders[] = ucwords($h, '-').': '.$v;
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [$status, is_string($response) ? $response : '', $error];
    }

    /** COS 签名编码：RFC3986（A-Za-z0-9-_.~ 之外全部百分号编码） */
    protected static function cosEncode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}

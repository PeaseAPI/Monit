<?php

namespace App\Support;

/**
 * 纯 PHP 阿里云 OSS 客户端（Header 签名，M17 规格书 §14.8 对象存储扩展）
 * 接口与 S3Client 对齐：put / get / delete / exists，返回 [status, body, error]。
 *
 * 签名方式：OSS Header 签名
 *   Authorization: OSS {AccessKeyId}:{base64(hmac_sha1(StringToSign, AccessKeySecret))}
 *   StringToSign = VERB\nContent-MD5\nContent-Type\nDate\nCanonicalizedOSSHeaders + CanonicalizedResource
 *
 * 地址风格：virtual-hosted —— https://{bucket}.{endpoint-host}/{key}
 * 无外部依赖。
 */
class AliyunOssClient
{
    public function __construct(
        protected string $accessKeyId,
        protected string $accessKeySecret,
        protected string $bucket,
        protected string $endpoint = 'https://oss-cn-hangzhou.aliyuncs.com',
    ) {
    }

    /** 对象完整 URL（virtual-hosted style） */
    public function urlFor(string $key): string
    {
        $endpoint = rtrim($this->endpoint, '/');
        $scheme = (string) (parse_url($endpoint, PHP_URL_SCHEME) ?: 'https');
        $host = (string) parse_url($endpoint, PHP_URL_HOST);

        return "{$scheme}://{$this->bucket}.{$host}/" . ltrim($key, '/');
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

    /* ---------------- OSS Header 签名 ---------------- */

    protected function request(string $method, string $key, ?string $body = null, array $headers = []): array
    {
        $url = $this->urlFor($key);
        $host = (string) parse_url($url, PHP_URL_HOST);
        $date = now()->setTimezone('UTC')->format('D, d M Y H:i:s \G\M\T');
        $contentType = (string) ($headers['Content-Type'] ?? '');

        // CanonicalizedOSSHeaders：x-oss-* 头小写排序，每项以 \n 结尾
        $ossHeaders = [];
        foreach ($headers as $name => $value) {
            if (stripos((string) $name, 'x-oss-') === 0) {
                $ossHeaders[strtolower((string) $name)] = trim((string) $value);
            }
        }
        ksort($ossHeaders);
        $canonicalOssHeaders = '';
        foreach ($ossHeaders as $h => $v) {
            $canonicalOssHeaders .= $h . ':' . $v . "\n";
        }

        // CanonicalizedResource：/{bucket}/{key}
        $resource = '/' . $this->bucket . '/' . ltrim($key, '/');

        $stringToSign = implode("\n", [
            $method,
            $headers['Content-MD5'] ?? '',
            $contentType,
            $date,
            $canonicalOssHeaders . $resource,
        ]);

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessKeySecret, true));

        $curlHeaders = [
            'Authorization: OSS ' . $this->accessKeyId . ':' . $signature,
            'Date: ' . $date,
        ];
        foreach ($headers as $h => $v) {
            if (strcasecmp((string) $h, 'host') !== 0) {
                $curlHeaders[] = ucwords((string) $h, '-') . ': ' . $v;
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
}

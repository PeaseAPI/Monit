<?php

namespace App\Support;

/**
 * 纯 PHP S3 兼容客户端（AWS Signature V4，path-style）
 * 用于 Offload 插件（规格书 §14.8）：支持 S3 / MinIO / 兼容对象存储。
 * 仅实现 PUT / GET / DELETE / HEAD，无外部依赖。
 */
class S3Client
{
    public function __construct(
        protected string $accessKey,
        protected string $secretKey,
        protected string $bucket,
        protected string $region = 'us-east-1',
        protected string $endpoint = '',   // 自定义端点（MinIO 等）；空则 AWS
    ) {
    }

    /** 构建 API 端点（path-style） */
    protected function baseUrl(): string
    {
        if ($this->endpoint !== '') {
            return rtrim($this->endpoint, '/') . '/' . $this->bucket;
        }

        return 'https://s3.' . $this->region . '.amazonaws.com/' . $this->bucket;
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

    /* ---------------- SigV4 ---------------- */

    protected function request(string $method, string $key, ?string $body = null, array $headers = []): array
    {
        $url = $this->baseUrl() . '/' . ltrim($key, '/');
        $host = (string) parse_url($url, PHP_URL_HOST);

        $amzDate = now()->setTimezone('UTC')->format('Ymd\THis\Z');
        $dateStamp = substr($amzDate, 0, 8);
        $payloadHash = hash('sha256', (string) $body);

        $allHeaders = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
            ...array_change_key_case($headers, CASE_LOWER),
        ];
        ksort($allHeaders);

        $canonicalHeaders = '';
        foreach ($allHeaders as $h => $v) {
            $canonicalHeaders .= strtolower($h) . ':' . trim((string) $v) . "\n";
        }
        $signedHeaders = implode(';', array_keys($allHeaders));

        $canonicalRequest = implode("\n", [
            $method,
            '/' . $this->bucket . '/' . ltrim($key, '/'),
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = $dateStamp . '/' . $this->region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $scope,
            hash('sha256', $canonicalRequest),
        ]);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $auth = 'AWS4-HMAC-SHA256 Credential=' . $this->accessKey . '/' . $scope
            . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

        $curlHeaders = ['Authorization: ' . $auth];
        foreach ($allHeaders as $h => $v) {
            if ($h !== 'host') {
                $curlHeaders[] = ucwords($h, '-') . ': ' . $v;
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

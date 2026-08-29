<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Ed25519 离线签名 License 管理器（规格书 §15.2）
 *
 * License 文件格式（storage/app/license.json）：
 * {
 *   "license_id": "LIC-xxxx", "product": "monit",
 *   "domains": ["analytics.example.com"], "max_domains": 5,
 *   "expires": "2027-12-31", "features": {...},
 *   "signature": "<Ed25519 签名（对除 signature 外字段的规范 JSON 串）>"
 * }
 *
 * 验证流程：读取 → sodium 验签 → 域名匹配 → 有效期 → 缓存 1 小时
 */
class LicenseManager
{
    public const CACHE_KEY = 'monit.license.status';

    public const PRODUCT = 'monit';

    /** 一小时重验一次 */
    public const CACHE_TTL = 3600;

    /**
     * 获取验证结果（带缓存）
     *
     * @return array{valid: bool, reason: string, data: ?array}
     */
    public static function status(bool $refresh = false): array
    {
        if (! $refresh) {
            $cached = Cache::get(self::CACHE_KEY);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = self::doVerify();
        Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * 实际验证逻辑
     */
    protected static function doVerify(): array
    {
        $path = self::licensePath();

        if (! is_file($path)) {
            return ['valid' => false, 'reason' => 'missing', 'data' => null];
        }

        $json = file_get_contents($path);

        if ($json === false) {
            return ['valid' => false, 'reason' => 'unreadable', 'data' => null];
        }

        $license = json_decode($json, true);

        if (! is_array($license) || empty($license['signature'])) {
            return ['valid' => false, 'reason' => 'malformed', 'data' => null];
        }

        // 1. Ed25519 验签（对除 signature 外字段的规范 JSON 串）
        if (! self::verifySignature($license)) {
            return ['valid' => false, 'reason' => 'bad_signature', 'data' => null];
        }

        // 2. 产品标识
        if (($license['product'] ?? null) !== self::PRODUCT) {
            return ['valid' => false, 'reason' => 'wrong_product', 'data' => $license];
        }

        // 3. 域名匹配（当前 APP_URL host ∈ domains，支持 *.example.com 通配）
        if (! self::domainMatches((array) ($license['domains'] ?? []))) {
            return ['valid' => false, 'reason' => 'domain_mismatch', 'data' => $license];
        }

        // 4. 有效期
        if (! self::notExpired((string) ($license['expires'] ?? ''))) {
            return ['valid' => false, 'reason' => 'expired', 'data' => $license];
        }

        return ['valid' => true, 'reason' => 'ok', 'data' => $license];
    }

    /**
     * Ed25519 detached 签名校验
     */
    public static function verifySignature(array $license): bool
    {
        $signature = (string) ($license['signature'] ?? '');

        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES * 2
            || ! ctype_xdigit($signature)) {
            return false;
        }

        $payload = self::canonicalJson($license);

        return sodium_crypto_sign_verify_detached(
            hex2bin($signature),
            $payload,
            hex2bin(self::publicKey()),
        );
    }

    /**
     * 规范化待签名字符串（剔除 signature，按键排序 + unescape unicode）
     */
    public static function canonicalJson(array $license): string
    {
        unset($license['signature']);

        return self::jsonEncode($license);
    }

    /**
     * 稳定 JSON 编码（键排序、紧凑、不转义斜杠与 unicode）
     */
    public static function jsonEncode(array $data): string
    {
        ksort($data, SORT_STRING);

        return json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * 当前域名是否被授权（支持精确匹配与 *.tld 通配）
     */
    public static function domainMatches(array $domains): bool
    {
        $host = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost');

        foreach ($domains as $domain) {
            $domain = strtolower(trim((string) $domain));

            if ($domain === '' || $domain === $host || $domain === '*') {
                return true;
            }

            // *.example.com 通配
            if (str_starts_with($domain, '*.')) {
                $suffix = substr($domain, 1); // ".example.com"

                if (str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 是否在有效期内（空 = 永久）
     */
    public static function notExpired(string $expires): bool
    {
        if ($expires === '') {
            return true;
        }

        $ts = strtotime($expires);

        return $ts !== false && $ts >= strtotime(date('Y-m-d'));
    }

    /**
     * License 文件路径
     */
    public static function licensePath(): string
    {
        return config('monit.license.path', storage_path('app/license.json'));
    }

    /**
     * 内置公钥（hex）；可用 MONIT_LICENSE_PUBLIC_KEY 覆盖
     */
    public static function publicKey(): string
    {
        return (string) (config('monit.license.public_key')
            ?? trim((string) env('MONIT_LICENSE_PUBLIC_KEY', '')));
    }
}

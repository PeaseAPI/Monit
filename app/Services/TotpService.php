<?php

namespace App\Services;

/**
 * TOTP 两步验证服务（规格书 §12.4）
 * 纯 PHP 实现 RFC 6238（Time-based One-time Password）+ RFC 4648 Base32，
 * 与 Google Authenticator / 1Password / Authy 等认证器完全兼容。
 */
class TotpService
{
    /** 时间步长（秒） */
    public const PERIOD = 30;

    /** 校验码位数 */
    public const DIGITS = 6;

    /** 密钥长度（字节，Base32 编码后 32 字符） */
    public const SECRET_BYTES = 20;

    /**
     * 生成随机 Base32 密钥
     */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /**
     * 根据密钥与时间计数器计算 TOTP 码
     */
    public static function code(string $secret, ?int $counter = null): string
    {
        $counter ??= intdiv(time(), self::PERIOD);
        $key = self::base32Decode($secret);

        // RFC 4226：计数器按大端 8 字节打包，HMAC-SHA1 后动态截断
        $hash = hash_hmac('sha1', pack('N2', $counter >> 32, $counter & 0xFFFFFFFF), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * 校验动态码（允许 ±1 个时间窗口的时钟偏移）
     */
    public static function verify(string $secret, ?string $code): bool
    {
        return self::verifyCounter($secret, $code) !== null;
    }

    /**
     * 校验动态码并返回命中的时间计数器（RFC 6238 ±1 窗口；恒定时间比较防时序攻击）
     *
     * 返回 null 表示不匹配；返回 int 供一次性消费（consume）判重使用
     */
    public static function verifyCounter(string $secret, ?string $code): ?int
    {
        if (! $code || ! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return null;
        }

        $counter = intdiv(time(), self::PERIOD);

        foreach ([$counter - 1, $counter, $counter + 1] as $candidate) {
            if (hash_equals(self::code($secret, $candidate), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * 一次性消费动态码（RFC 6238 §5.2 建议的 last-used counter 判重）
     *
     * 同一时间窗口的码只允许使用一次：命中后把 counter 记入 cache
     * （TTL 120s 覆盖 counter+1 窗口的最长有效期 90s），
     * 后续相同或更小 counter 的码一律拒绝——阻断钓鱼/拦截后的 30~90 秒重放窗口。
     * cache 不可用（null 驱动）时退化为纯校验，不阻断可用性。
     */
    public static function consume(string $secret, ?string $code, string $consumerKey): bool
    {
        $matched = self::verifyCounter($secret, $code);

        if ($matched === null) {
            return false;
        }

        $cacheKey = 'twofa.last_counter.'.$consumerKey;
        $lastUsed = (int) cache()->get($cacheKey, 0);

        if ($matched <= $lastUsed) {
            return false;
        }

        cache()->put($cacheKey, $matched, now()->addSeconds(120));

        return true;
    }

    /**
     * 生成 otpauth:// URI（认证器扫码用）
     */
    public static function uri(string $secret, string $email, string $issuer = 'Monit'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD,
        );
    }

    /**
     * 外部 QR 码图片地址（与原系统一致使用 qrserver API）
     */
    public static function qrImageUrl(string $uri, int $size = 200): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&data='.rawurlencode($uri);
    }

    /* -----------------------------------------------------------------
     | Base32 编解码（RFC 4648）
     ----------------------------------------------------------------- */

    public static function base32Encode(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $buffer = 0;
        $bits = 0;

        foreach (str_split($bytes) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bits += 8;

            while ($bits >= 5) {
                $output .= $alphabet[($buffer >> ($bits - 5)) & 31];
                $bits -= 5;
            }
        }

        if ($bits > 0) {
            $output .= $alphabet[($buffer << (5 - $bits)) & 31];
        }

        return $output;
    }

    public static function base32Decode(string $encoded): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bits = 0;
        $output = '';

        foreach (str_split(strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $encoded))) as $char) {
            $position = strpos($alphabet, $char);

            if ($position === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $position;
            $bits += 5;

            if ($bits >= 8) {
                $output .= chr(($buffer >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $output;
    }
}

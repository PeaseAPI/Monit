<?php

namespace App\Services\Sms;

use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 短信验证码服务（M17 规格书 §12.5）
 *
 * 场景：register（注册）/ login（手机号登录）/ forgot_password（找回密码）/ phone_bind（绑定手机号）
 * 服务商：aliyun（阿里云）/ tencent（腾讯云）/ log（开发调试，只写日志不发短信）
 *
 * 验证码存 Cache：monit.sms.{purpose}.{phone}（默认 10 分钟有效）
 * 发送节流：monit.sms.throttle.{phone}（默认 60s 一次）
 * 防爆破：同一 purpose 连错 5 次作废当前验证码
 *
 * 配置来源：管理后台「短信验证」设置组（settings sms.*）优先，回落 config/services.php（env）。
 */
class SmsService
{
    public const PURPOSES = ['register', 'login', 'forgot_password', 'phone_bind'];

    public const PROVIDERS = ['aliyun', 'tencent', 'log'];

    /** 短信功能总开关 */
    public static function isEnabled(): bool
    {
        // 设置存储为 'true'/'false' 字符串（saveSettings 约定）：(bool)'false' 为 true，须 filter_var 归一化
        return filter_var(Settings::get('sms.sms_is_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** 场景开关（register / phone_login / forgot_password / phone_bind） */
    public static function scenarioEnabled(string $scenario): bool
    {
        if (! static::isEnabled()) {
            return false;
        }

        return filter_var(Settings::get("sms.sms_{$scenario}_is_enabled", false), FILTER_VALIDATE_BOOLEAN);
    }

    /** 当前短信服务商 */
    public static function provider(): string
    {
        $provider = (string) Settings::get('sms.sms_provider', '');

        if ($provider === '') {
            $provider = (string) config('services.sms.provider', 'log');
        }

        return in_array($provider, static::PROVIDERS, true) ? $provider : 'log';
    }

    /**
     * 手机号规范化：去空白/横线/括号；+86 或 86 前缀的 13 位归一为国内 11 位
     */
    public static function normalizePhone(string $phone): string
    {
        $trimmed = trim($phone);
        $hasPlus = str_starts_with($trimmed, '+');
        $digits = (string) preg_replace('/\D/', '', $trimmed);

        if (($hasPlus || strlen($digits) > 11) && str_starts_with($digits, '86') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /** 是否为可用的国内手机号 */
    public static function isPhone(string $value): bool
    {
        return (bool) preg_match('/^1[3-9]\d{9}$/', static::normalizePhone($value));
    }

    /**
     * 发送验证码
     *
     * @return array{0: bool, 1: string} [是否成功, 错误码]
     */
    public static function send(string $phone, string $purpose): array
    {
        if (! in_array($purpose, static::PURPOSES, true)) {
            return [false, 'invalid_purpose'];
        }

        $phone = static::normalizePhone($phone);

        if (! preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return [false, 'invalid_phone'];
        }

        $ttl = max(1, (int) (Settings::get('sms.sms_code_ttl_minutes', 10) ?: 10));
        $interval = max(10, (int) (Settings::get('sms.sms_resend_interval_seconds', 60) ?: 60));

        // 发送节流
        if (! Cache::add("monit.sms.throttle.{$phone}", 1, $interval)) {
            return [false, 'too_frequent'];
        }

        $code = (string) random_int(100000, 999999);

        [$ok, $error] = static::dispatch($phone, $code, $purpose);

        if (! $ok) {
            Cache::forget("monit.sms.throttle.{$phone}");

            return [false, $error];
        }

        Cache::put("monit.sms.{$purpose}.{$phone}", $code, now()->addMinutes($ttl));
        Cache::forget("monit.sms.attempts.{$purpose}.{$phone}");

        return [true, ''];
    }

    /** 校验验证码（通过即作废，一次性） */
    public static function verify(string $phone, string $purpose, string $code): bool
    {
        $phone = static::normalizePhone($phone);
        $cacheKey = "monit.sms.{$purpose}.{$phone}";
        $expected = Cache::get($cacheKey);

        if (! $expected || ! hash_equals((string) $expected, trim($code))) {
            $attemptsKey = "monit.sms.attempts.{$purpose}.{$phone}";
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;

            if ($attempts >= 5) {
                Cache::forget($cacheKey);
                Cache::forget($attemptsKey);
            } else {
                Cache::put($attemptsKey, $attempts, now()->addMinutes(10));
            }

            return false;
        }

        Cache::forget($cacheKey);
        Cache::forget("monit.sms.attempts.{$purpose}.{$phone}");

        return true;
    }

    /** 分发到短信服务商 */
    protected static function dispatch(string $phone, string $code, string $purpose): array
    {
        try {
            return match (static::provider()) {
                'aliyun' => AliyunSmsProvider::make()->send($phone, $code),
                'tencent' => TencentSmsProvider::make()->send($phone, $code),
                default => static::logSend($phone, $code, $purpose),
            };
        } catch (\Throwable $e) {
            report($e);

            return [false, 'provider_error'];
        }
    }

    /** log 驱动：验证码写日志（开发/测试用） */
    protected static function logSend(string $phone, string $code, string $purpose): array
    {
        Log::channel(config('logging.default'))->info("[SMS:{$purpose}] {$phone} => {$code}");

        return [true, ''];
    }
}

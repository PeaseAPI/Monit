<?php

namespace App\Services;

use App\Support\Settings;

/**
 * 登录/找回密码失败锁定（规格书后台 users.login_lockout_* /
 * users.lost_password_lockout_* 设置的消费方）
 *
 * 审计缺口：两组设置在后台可配且默认启用，但登录与找回密码流程
 * 此前完全未消费——管理员以为开启的爆破防护实际不存在。
 *
 * 语义（对标原版）：同一标识（邮箱/手机号）连续 N 次失败后锁定 M 分钟；
 * 锁定期间正确凭证也被拒绝；成功登录清零计数。计数与锁定均存 cache
 * （TTL = 锁定分钟数），cache 不可用时退化为无锁定（不阻断可用性）。
 */
class LoginLockout
{
    /** scope → [开关, 最大失败次数, 锁定分钟] 设置键；默认值与后台表单 placeholder 一致 */
    private const SCOPES = [
        'login' => [
            'users.login_lockout_is_enabled', 'users.login_lockout_max_retries', 'users.login_lockout_time',
            5,
        ],
        'lost_password' => [
            'users.lost_password_lockout_is_enabled', 'users.lost_password_lockout_max_retries', 'users.lost_password_lockout_time',
            3,
        ],
        'activation' => [
            'users.activation_resend_lockout_is_enabled', 'users.activation_resend_lockout_max_retries', 'users.activation_resend_lockout_time',
            3,
        ],
    ];

    public static function blocked(string $scope, string $identifier): bool
    {
        return cache()->has(self::key($scope, $identifier, 'locked'));
    }

    public static function recordFailure(string $scope, string $identifier): void
    {
        [, , , $defaultRetries] = self::SCOPES[$scope] ?? [null, null, null, 5];

        [$enabled, $retries, $minutes] = self::config($scope);

        if (! $enabled) {
            return;
        }

        $retries = max(1, (int) ($retries ?: $defaultRetries));
        $minutes = max(1, (int) ($minutes ?: 30));

        $failsKey = self::key($scope, $identifier, 'fails');
        $fails = (int) cache()->get($failsKey, 0) + 1;

        if ($fails >= $retries) {
            // 触发锁定：写锁定标记并清零计数（解锁后重新累计）
            cache()->put(self::key($scope, $identifier, 'locked'), true, now()->addMinutes($minutes));
            cache()->forget($failsKey);
        } else {
            cache()->put($failsKey, $fails, now()->addMinutes($minutes));
        }
    }

    public static function clear(string $scope, string $identifier): void
    {
        cache()->forget(self::key($scope, $identifier, 'fails'));
        cache()->forget(self::key($scope, $identifier, 'locked'));
    }

    /**
     * @return array{0: bool, 1: int, 2: int} [enabled, retries, minutes]
     */
    private static function config(string $scope): array
    {
        [$enabledKey, $retriesKey, $minutesKey, $defaultRetries] = self::SCOPES[$scope];

        // 开关默认启用（与后台表单默认 checked 一致）；存储为 'true'/'false' 字符串须归一化
        $raw = Settings::get($enabledKey);
        $enabled = $raw === null || $raw === '' ? true : filter_var($raw, FILTER_VALIDATE_BOOLEAN);

        return [
            $enabled,
            (int) (Settings::get($retriesKey) ?: $defaultRetries),
            (int) (Settings::get($minutesKey) ?: 30),
        ];
    }

    private static function key(string $scope, string $identifier, string $kind): string
    {
        // 大小写归一（MySQL ci collation 下 A@x.com 与 a@x.com 是同一账户）
        return 'lockout.'.$kind.'.'.$scope.'.'.md5(mb_strtolower(trim($identifier)));
    }
}

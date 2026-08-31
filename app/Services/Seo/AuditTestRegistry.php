<?php

namespace App\Services\Seo;

use App\Support\Settings;

/**
 * 审计测试注册表：读取 config/seo.php，跳过未配置外部凭据的条件项
 */
class AuditTestRegistry
{
    /**
     * @return array<string, array{category:string, importance:string, requires?:string}>
     */
    public static function all(): array
    {
        return collect(config('seo.tests', []))
            ->filter(fn (array $meta, string $key) => static::requirementsMet($meta))
            ->all();
    }

    /**
     * 后台阈值（settings seo 组覆盖 config 默认值）
     */
    public static function threshold(string $key, mixed $default = null): mixed
    {
        return Settings::get('seo.seo_'.$key) ?? config("seo.thresholds.{$key}", $default);
    }

    public static function weightOf(string $importance): int
    {
        return match ($importance) {
            'major' => 3,
            'moderate' => 2,
            default => 1,
        };
    }

    public static function categories(): array
    {
        return array_keys(config('seo.categories', []));
    }

    protected static function requirementsMet(array $meta): bool
    {
        if (empty($meta['requires'])) {
            return true;
        }

        $value = Settings::get($meta['requires']);

        // 布尔开关须为 true；密钥类须为非空字符串
        return $value === true || $value === 'true' || (is_string($value) && trim($value) !== '');
    }
}

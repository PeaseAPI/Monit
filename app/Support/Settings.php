<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Monit 系统设置（settings 表 key-value + 缓存）
 * 对应原系统 settings() 函数与 Settings 模型
 */
class Settings
{
    protected static ?\stdClass $cached = null;

    public static function load(): \stdClass
    {
        if (static::$cached !== null) {
            return static::$cached;
        }

        // 缓存以数组存储（反序列化安全），读取后重建为对象，坏条目自动重建
        $cached = Cache::remember(
            'monit.settings',
            now()->addHours(12),
            function () {
                return static::buildArray();
            }
        );

        if (! is_array($cached)) {
            Cache::forget('monit.settings');
            $cached = static::buildArray();
        }

        return static::$cached = static::arrayToObject($cached);
    }

    protected static function buildArray(): array
    {
        $settings = [];

        foreach (Setting::query()->get() as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        return $settings;
    }

    protected static function arrayToObject(array $data): \stdClass
    {
        $object = new \stdClass;

        foreach ($data as $key => $value) {
            // key 支持点分层级：main.title -> main:{title}
            $parts = explode('.', $key);
            $node = $object;

            foreach ($parts as $i => $part) {
                if ($i === count($parts) - 1) {
                    $node->{$part} = $value;
                } else {
                    if (! isset($node->{$part}) || ! is_object($node->{$part})) {
                        $node->{$part} = new \stdClass;
                    }
                    $node = $node->{$part};
                }
            }
        }

        return $object;
    }

    /**
     * 读取设置（点分路径）：settings_get('main.title', 'Monit')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $node = static::load();

        foreach (explode('.', $key) as $part) {
            if (! isset($node->{$part})) {
                return $default;
            }
            $node = $node->{$part};
        }

        return $node;
    }

    /**
     * 写入设置（点分路径 -> 扁平 key 存储）并清缓存
     */
    public static function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        static::flush();
    }

    public static function flush(): void
    {
        Cache::forget('monit.settings');
        static::$cached = null;
    }
}

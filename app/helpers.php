<?php

use App\Support\Settings;

if (! function_exists('settings')) {
    /**
     * 读取系统设置（settings 表 → 缓存对象树）
     *
     * 用法：
     *   settings()              → 完整对象树（stdClass）
     *   settings()->analytics   → 分组对象
     *   settings_get('main.site_title', 'Monit')  → 单个值（推荐）
     */
    function settings(): stdClass
    {
        return Settings::load();
    }
}

if (! function_exists('settings_get')) {
    /**
     * 读取单个设置值（点分路径 + 默认值）
     *
     * 用法：settings_get('main.site_title', 'Monit')
     */
    function settings_get(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('stat_time')) {
    /**
     * 统计时间渲染：数据库统一存 UTC（now()，app.timezone=UTC）。
     *
     * 两层折算，杜绝把 UTC 当本地时间直出：
     *  1. 服务端按查看者账号偏好时区（auth user.timezone，未设置则回退
     *     app.timezone）折算出初始文本；
     *  2. 同时把 UTC 基准写入 <time data-utc>，页面加载后由全局脚本
     *     （layouts/app.blade.php）再折算为浏览器本地时区（查看者默认
     *     语言地区时区）。JS 被禁用时保留第 1 层结果，兜底仍正确。
     *
     * 用法：{!! stat_time($replay->datetime) !!}
     *       {!! stat_time($event->date, 'H:i:s') !!}
     *       {!! stat_time($profile['first_date'], 'Y-m-d H:i', 'font-medium text-zinc-700') !!}
     */
    function stat_time(mixed $value, string $format = 'Y-m-d H:i:s', string $class = ''): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            $utc = $value instanceof \Carbon\CarbonInterface
                ? $value->copy()
                : \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return '—';
        }

        $tz = auth()->user()?->timezone ?: config('app.timezone');

        try {
            $local = (clone $utc)->timezone($tz);
        } catch (\Throwable) {
            // 账号偏好里的时区值异常（如手改数据库）时回退 UTC，避免整页 500
            $local = clone $utc;
            $tz = 'UTC';
        }

        $iso = $utc->timezone('UTC')->toIso8601String();

        return '<time data-utc="' . $iso . '"' . ($class !== '' ? ' class="' . e($class) . '"' : '')
            . '>' . e($local->format($format)) . '</time>';
    }
}

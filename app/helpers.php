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

<?php

namespace App\Support;

use App\Models\Plugin;

/**
 * 插件管理器（规格书 §14）
 *
 * 插件目录结构：
 *   plugins/{plugin-id}/config.php    # 返回元数据数组（id,title,description,version,author,url,settings）
 *   plugins/{plugin-id}/init.php      # 激活插件的启动入口（注册路由 / Blade 指令 / 监听器）
 *   plugins/{plugin-id}/install.php   # 安装钩子（建表 / 写默认值）
 *   plugins/{plugin-id}/uninstall.php # 卸载钩子（清数据）
 *
 * 状态机：uninstalled(无DB行) → installed(0) → active(1)
 */
class PluginManager
{
    /** 已 boot 的插件（防止重复 include） */
    protected static array $booted = [];

    /* -----------------------------------------------------------------
     | 扫描与状态
     ----------------------------------------------------------------- */

    /**
     * 扫描插件目录并与 DB 状态合并
     *
     * @return array<int, array<string, mixed>>
     */
    public static function scan(): array
    {
        $rows = Plugin::query()->get()->keyBy('plugin_id');
        $plugins = [];

        foreach (glob(static::path('*' . DIRECTORY_SEPARATOR . 'config.php')) ?: [] as $file) {
            $meta = include $file;

            if (! is_array($meta) || empty($meta['id'])) {
                continue;
            }

            $row = $rows->get($meta['id']);

            $plugins[] = [
                'id' => $meta['id'],
                'title' => $meta['title'] ?? $meta['id'],
                'description' => $meta['description'] ?? '',
                'version' => $meta['version'] ?? '1.0.0',
                'author' => $meta['author'] ?? 'Monit',
                'url' => $meta['url'] ?? '',
                'settings' => $meta['settings'] ?? [],
                'installed' => (bool) ($row?->is_installed ?? false),
                'active' => (bool) ($row?->is_active ?? false),
                'row_settings' => $row?->settings ?? [],
            ];
        }

        return $plugins;
    }

    public static function meta(string $id): ?array
    {
        foreach (static::scan() as $plugin) {
            if ($plugin['id'] === $id) {
                return $plugin;
            }
        }

        return null;
    }

    public static function exists(string $id): bool
    {
        return is_file(static::path($id, 'config.php'));
    }

    public static function isActive(string $id): bool
    {
        return (bool) Plugin::query()->where('plugin_id', $id)->value('is_active');
    }

    /**
     * 读取插件设置（DB 值优先，缺省回落 config 默认值）
     */
    public static function setting(string $id, string $key, mixed $default = null): mixed
    {
        $row = Plugin::query()->find($id);

        return $row?->settings[$key] ?? $default;
    }

    /* -----------------------------------------------------------------
     | 状态机（规格书 §14.2）
     ----------------------------------------------------------------- */

    /** 安装：写 DB 行 + 默认设置 + 执行 install.php */
    public static function install(string $id): void
    {
        $meta = static::requireMeta($id);

        $defaults = [];
        foreach ($meta['settings'] ?? [] as $key => $definition) {
            $defaults[$key] = $definition['default'] ?? null;
        }

        Plugin::query()->updateOrCreate(
            ['plugin_id' => $id],
            [
                'name' => $meta['title'] ?? $id,
                'is_installed' => true,
                'is_active' => false,
                'settings' => $defaults,
                'datetime' => now(),
            ],
        );

        static::runHook($id, 'install.php');
    }

    /** 激活 */
    public static function activate(string $id): void
    {
        static::requireMeta($id);

        Plugin::query()->where('plugin_id', $id)->update([
            'is_installed' => true,
            'is_active' => true,
        ]);

        static::runHook($id, 'init.php');
    }

    /** 停用 */
    public static function deactivate(string $id): void
    {
        Plugin::query()->where('plugin_id', $id)->update(['is_active' => false]);

        static::runHook($id, 'deactivate.php');
    }

    /** 卸载：uninstall.php → 删 DB 行 */
    public static function uninstall(string $id): void
    {
        static::runHook($id, 'uninstall.php');

        Plugin::query()->where('plugin_id', $id)->delete();
    }

    /**
     * 保存插件设置（仅允许 config settings 定义过的键）
     */
    public static function saveSettings(string $id, array $values): void
    {
        $meta = static::requireMeta($id);

        $allowed = $meta['settings'] ?? [];
        $row = Plugin::query()->findOrFail($id);

        $settings = $row->settings ?? [];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $allowed)) {
                $settings[$key] = ($allowed[$key]['type'] ?? 'text') === 'bool'
                    ? (bool) $value
                    : $value;
            }
        }

        $row->update(['settings' => $settings]);
    }

    /* -----------------------------------------------------------------
     | Boot（AppServiceProvider::boot 调用，激活所有 active 插件）
     ----------------------------------------------------------------- */

    public static function boot(): void
    {
        $activeIds = Plugin::query()->where('is_active', true)->pluck('plugin_id');

        foreach ($activeIds as $id) {
            if (in_array($id, static::$booted, true)) {
                continue;
            }

            static::$booted[] = $id;
            static::runHook($id, 'init.php');
        }
    }

    /* -----------------------------------------------------------------
     | 工具
     ----------------------------------------------------------------- */

    protected static function requireMeta(string $id): array
    {
        $meta = static::meta($id);

        if ($meta === null) {
            throw new \InvalidArgumentException("Plugin [{$id}] not found.");
        }

        return $meta;
    }

    protected static function runHook(string $id, string $file): void
    {
        $path = static::path($id, $file);

        if (is_file($path)) {
            include $path;
        }
    }

    /**
     * 插件文件路径：PluginManager::path('pwa', 'init.php')
     */
    public static function path(string ...$parts): string
    {
        return base_path(implode(DIRECTORY_SEPARATOR, array_merge(['plugins'], $parts)));
    }
}


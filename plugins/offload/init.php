<?php

use App\Support\PluginManager;
use Illuminate\Support\Facades\Blade;

/**
 * Offload 启动入口（规格书 §14.8）
 * - 注册全局 helper monit_offload_asset() 与 Blade 指令 @offloadAsset：静态资源 CDN 前缀替换
 *
 * 配置源统一：原启动时写入的 offload.is_enabled 为零消费死键
 * （且每次请求触发一次 DB 写 + 缓存失效），不再维护；插件激活态
 * 由 plugins.is_active 表达，存储配置由「系统设置 → 存储卸载」
 * 组与 Offload 插件页分层管理（ObjectStorage 三层回落）。
 */

// 静态资源 CDN：插件启用且配置了 cdn_url 时，把 /assets、/uploads 前缀替换为 CDN
if (! function_exists('monit_offload_asset')) {
    function monit_offload_asset(string $path): string
    {
        if (! PluginManager::isActive('offload')) {
            return $path;
        }

        $cdn = rtrim((string) PluginManager::setting('offload', 'cdn_url', ''), '/');

        if ($cdn === '' || ! str_starts_with($path, '/')) {
            return $path;
        }

        return $cdn.'/'.ltrim($path, '/');
    }
}

Blade::directive('offloadAsset', function (string $expression) {
    return "<?php echo e(monit_offload_asset({$expression})); ?>";
});

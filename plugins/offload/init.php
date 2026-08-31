<?php

use App\Support\PluginManager;
use App\Support\Settings;
use Illuminate\Support\Facades\Blade;

/**
 * Offload 启动入口（规格书 §14.8）
 * - 开启功能标记
 * - 注册全局 helper monit_offload_asset() 与 Blade 指令 @offloadAsset：静态资源 CDN 前缀替换
 */
Settings::set('offload.is_enabled', true);

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

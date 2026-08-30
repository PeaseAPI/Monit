<?php

/**
 * Image Optimizer 启动入口（规格书 §14.9）
 * - 开启功能标记
 * - 注册上传拦截 helper：monit_image_optimize($path, $userId) —— 任何上传管线调用即自动压缩
 * - 注册 Admin 端点：批量优化 uploads/ 目录 + 统计页
 */

use App\Services\ImageOptimizer;
use App\Support\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

\App\Support\Settings::set('image_optimizer.is_enabled', true);

/* ---------------- 上传拦截 hook（规格书 §14.9 uploads_upload() 拦截） ---------------- */

if (! function_exists('monit_image_optimize')) {
    function monit_image_optimize(string $path, ?int $userId = null): array
    {
        if (! PluginManager::isActive('image-optimizer')) {
            return ['ok' => false, 'skipped' => true];
        }

        $quality = (int) PluginManager::setting('image-optimizer', 'quality', 82);
        $keepOriginal = (bool) PluginManager::setting('image-optimizer', 'keep_original', false);

        return (new ImageOptimizer)->process($path, $quality, $keepOriginal, $userId);
    }
}

/* ---------------- Admin：批量优化 + 统计 ---------------- */

Route::middleware(['auth', 'admin'])->prefix('admin/plugins/image-optimizer')->group(function (): void {
    // 统计页 + 批量优化入口
    Route::get('/stats', function () {
        if (! PluginManager::isActive('image-optimizer')) {
            abort(404);
        }

        $totalOriginal = \App\Models\ImageOptimizerStat::sum('original_size');
        $totalOptimized = \App\Models\ImageOptimizerStat::sum('optimized_size');
        $saved = max(0, $totalOriginal - $totalOptimized);
        $savedPercent = $totalOriginal > 0 ? round($saved / $totalOriginal * 100, 1) : 0.0;
        $recent = \App\Models\ImageOptimizerStat::orderByDesc('stat_id')->limit(50)->get();

        return view('plugins.image-optimizer.stats', compact('totalOriginal', 'totalOptimized', 'saved', 'savedPercent', 'recent'))
            ->with('adminNav', 'plugins');
    })->name('admin.plugins.image-optimizer.stats');

    // 批量优化 uploads/ 目录（分批 50 张）
    Route::post('/batch', function (Request $request) {
        if (! PluginManager::isActive('image-optimizer')) {
            abort(404);
        }

        $quality = (int) PluginManager::setting('image-optimizer', 'quality', 82);
        $keepOriginal = (bool) PluginManager::setting('image-optimizer', 'keep_original', false);
        $optimizer = new ImageOptimizer;

        $dir = public_path('uploads');
        $files = glob($dir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE) ?: [];
        $processed = 0;
        $failed = 0;

        foreach (array_slice($files, 0, 50) as $file) {
            $result = $optimizer->process($file, $quality, $keepOriginal, $request->user()?->user_id);
            $result['ok'] && ! isset($result['error']) ? $processed++ : $failed++;
        }

        return back()->with('success', "批量优化完成：{$processed} 成功 / {$failed} 跳过或失败");
    })->name('admin.plugins.image-optimizer.batch');
});

<?php

namespace App\Console\Commands;

use App\Models\Heatmap;
use App\Services\HeatmapScreenshot;
use Illuminate\Console\Command;

/**
 * 热图三端服务端截图刷新（M30）
 *
 * 为所有启用的热图重新生成桌面/平板/手机标准视口截图
 * （desktop 1366 / tablet 768 / mobile 375），存 public/uploads/heatmap-shots/。
 * 由 console.php 每日 05:10 调度；--heatmap 可指定单条热图调试。
 */
class HeatmapShotsRefresh extends Command
{
    protected $signature = 'monit:heatmap-screenshots {--heatmap= : 仅处理指定 heatmap_id}';

    protected $description = '刷新热图三端标准视口截图（桌面/平板/手机底图存服务器）';

    public function handle(HeatmapScreenshot $screenshots): int
    {
        if (config('services.heatmap.chrome_bin') === null
            || ! is_file((string) config('services.heatmap.chrome_bin'))) {
            $this->warn('未配置 chrome-headless-shell（HEATMAP_CHROME_BIN），跳过截图刷新。');

            return self::SUCCESS;
        }

        $query = Heatmap::query()->where('is_enabled', true);
        $heatmapId = (int) $this->option('heatmap');
        if ($heatmapId > 0) {
            $query->where('heatmap_id', $heatmapId);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('没有需要截图的热图。');

            return self::SUCCESS;
        }

        $ok = 0;
        $query->orderBy('heatmap_id')->chunkById(20, function ($heatmaps) use (&$ok, $screenshots): void {
            foreach ($heatmaps as $heatmap) {
                $result = $screenshots->captureAll($heatmap);
                $generated = count(array_filter($result));
                $ok += ($generated > 0 ? 1 : 0);
                $this->line("[{$heatmap->heatmap_id}] {$heatmap->path} => {$generated}/3 端截图成功");
            }
        });

        $this->info("✓ 完成：{$ok}/{$total} 条热图已生成三端截图。");

        return self::SUCCESS;
    }
}

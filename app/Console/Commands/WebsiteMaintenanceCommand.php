<?php

namespace App\Console\Commands;

use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\LightweightEvent;
use App\Models\SessionReplay;
use App\Models\Website;
use Illuminate\Console\Command;

/**
 * 网站维护 Cron
 * 规格书 §7：清理过期数据、更新访客计数
 */
class WebsiteMaintenanceCommand extends Command
{
    protected $signature = 'monit:website-maintenance';

    protected $description;

    public function __construct()
    {
        parent::__construct();
        $this->description = __('console.maintenance_desc');
    }

    public function handle(): int
    {
        $now = now();

        // 1. 清理过期的热图快照数据
        HeatmapSnapshotClick::where('expiration_date', '<', $now->format('Y-m-d'))->delete();
        HeatmapSnapshotScroll::where('expiration_date', '<', $now->format('Y-m-d'))->delete();

        // 2. 清理过期的会话回放（sessions_replays 无 expiration_date，用 datetime + 留存期判定，
        //    与 AnalyticsCleanupCommand 口径一致；规格 §13.1）
        $replaysRetentionDays = (int) config('app.replays_retention_days', 30);
        SessionReplay::where('datetime', '<', $now->copy()->subDays($replaysRetentionDays))->delete();

        // 3. 清理过期的轻事件
        LightweightEvent::where('expiration_date', '<', $now->format('Y-m-d'))->delete();

        // 4. 更新每月的计数（重置到0如果月份改变）
        Website::where('is_enabled', true)->get()->each(function ($website) use ($now) {
            $currentMonth = $now->format('Y-m');
            $statsMonth = $website->stats_month;

            if ($statsMonth !== $currentMonth) {
                $website->update([
                    'stats_month' => $currentMonth,
                    'current_month_sessions_events' => 0,
                    'current_month_events_children' => 0,
                    'current_month_sessions_replays' => 0,
                    'last_24_hours_pageviews' => 0,
                    'last_7_days_pageviews' => 0,
                ]);
            }
        });

        $this->info(__('console.maintenance_done'));

        return self::SUCCESS;
    }
}

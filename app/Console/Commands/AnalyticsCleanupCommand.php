<?php

namespace App\Console\Commands;

use App\Models\EventChild;
use App\Models\GoalConversion;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\LightweightEvent;
use App\Models\OutboundClick;
use App\Models\SessionEvent;
use App\Models\SessionReplay;
use App\Support\Typed;
use Illuminate\Console\Command;

/**
 * 分析数据清理 Cron
 * 规格书 §13：每日删除过期分析数据；清 pageviews_stats_last_datetime=null
 */
class AnalyticsCleanupCommand extends Command
{
    protected $signature = 'monit:analytics-cleanup';

    protected $description = '清理过期的分析数据';

    public function handle(): int
    {
        $now = now();
        $retentionDays = Typed::int(config('monit.pixel.events_retention_days', 365));
        $replaysRetentionDays = Typed::int(config('monit.pixel.replays_retention_days', 30));
        $cutoffDate = $now->copy()->subDays($retentionDays)->format('Y-m-d');
        $replaysCutoffDate = $now->copy()->subDays($replaysRetentionDays)->format('Y-m-d');

        // 1. 清理过期的 sessions_events
        $eventsDeleted = Typed::int(SessionEvent::where('expiration_date', '<', $cutoffDate)
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($eventsDeleted).' 条过期会话事件');

        // 2. 清理过期的 lightweight_events
        $lwDeleted = Typed::int(LightweightEvent::where('expiration_date', '<', $cutoffDate)
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($lwDeleted).' 条轻量事件');

        // 3. 清理过期的 events_children
        $childDeleted = Typed::int(EventChild::where('expiration_date', '<', $cutoffDate)
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($childDeleted).' 条事件子项');

        // 4. 清理过期的会话回放（短留存期）
        $replaysDeleted = Typed::int(SessionReplay::where('datetime', '<', $now->copy()->subDays($replaysRetentionDays))
            ->limit(500)
            ->delete());

        $this->info('已删除 '.Typed::string($replaysDeleted).' 条会话回放');

        // 5. 清理过期的热图数据
        $clicksDeleted = Typed::int(HeatmapSnapshotClick::where('expiration_date', '<', $cutoffDate)
            ->limit(5000)
            ->delete());

        $scrollsDeleted = Typed::int(HeatmapSnapshotScroll::where('expiration_date', '<', $cutoffDate)
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($clicksDeleted).' 条热图点击、'.Typed::string($scrollsDeleted).' 条热图滚动');

        // 6. 清理过期的出站点击
        $outboundDeleted = Typed::int(OutboundClick::where('datetime', '<', $now->copy()->subDays($retentionDays))
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($outboundDeleted).' 条出站点击');

        // 7. 清理过期的目标转化（goals_conversions 无 datetime 列：时间轴用 created_at；
        //    expiration_date 在转化创建时并未写入，恒为 null 不可作清理依据）
        $conversionsDeleted = Typed::int(GoalConversion::where('created_at', '<', $now->copy()->subDays($retentionDays))
            ->limit(5000)
            ->delete());

        $this->info('已删除 '.Typed::string($conversionsDeleted).' 条目标转化');

        return self::SUCCESS;
    }
}

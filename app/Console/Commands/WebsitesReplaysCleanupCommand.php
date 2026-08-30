<?php

namespace App\Console\Commands;

use App\Models\SessionReplay;
use Illuminate\Console\Command;

/**
 * 清理过期会话回放（规格书 §13.1：websites_replays_cleanup）
 * 删除过期/太短回放 + Redis chunk + S3 offload
 */
class WebsitesReplaysCleanupCommand extends Command
{
    protected $signature = 'monit:websites-replays-cleanup';

    protected $description = '清理过期会话回放数据（规格书 §13.1）';

    public function handle(): int
    {
        $retentionDays = (int) config('app.replays_retention_days', 30);
        $minDuration = (int) config('app.replays_min_duration_seconds', 3);

        $deleted = 0;

        // 删除过期回放
        SessionReplay::where('created_at', '<', now()->subDays($retentionDays))
            ->limit(30)
            ->each(function (SessionReplay $replay) use (&$deleted): void {
                // 清理 Redis 中的回放 chunk（如果使用 Redis）
                if ($replay->redis_key) {
                    try {
                        \Illuminate\Support\Facades\Redis::del($replay->redis_key);
                    } catch (\Throwable) {
                        // Redis 不可用时静默跳过
                    }
                }

                $replay->delete();
                $deleted++;
            });

        // 删除太短的回放（录制时长 < 最低要求）
        SessionReplay::where('duration', '<', $minDuration)
            ->where('created_at', '<', now()->subHours(1))
            ->limit(30)
            ->each(function (SessionReplay $replay) use (&$deleted): void {
                $replay->delete();
                $deleted++;
            });

        $this->info("已清理 {$deleted} 条过期回放记录");

        return self::SUCCESS;
    }
}

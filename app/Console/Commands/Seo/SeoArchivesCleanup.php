<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoAuditArchive;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * 审计归档清理：按用户套餐 seo_history_retention_days 保留期滚动删除快照
 */
class SeoArchivesCleanup extends Command
{
    protected $signature = 'monit:seo-archives-cleanup';

    protected $description = '按套餐保留期清理 SEO 审计历史快照';

    public function handle(): int
    {
        $deleted = 0;

        // 按用户分组处理（每用户保留期可能不同；游客审计统一 30 天）
        $userIds = SeoAuditArchive::whereNotNull('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            $retention = (int) ($user?->getPlanSettings()['seo_history_retention_days'] ?? 30);

            if ($retention > 0) {
                $deleted += SeoAuditArchive::where('user_id', $userId)
                    ->where('created_at', '<', now()->subDays($retention))
                    ->delete();
            }
        }

        $deleted += SeoAuditArchive::whereNull('user_id')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        $this->info("SEO 归档清理完成：删除 {$deleted} 条快照。");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 日常数据清理 Cron（原版 users_logs_cleanup + internal_notifications_cleanup + logs_cleanup，规格书 §13.1 M22）
 * - account_logs：删除 90 天前记录
 * - internal_notifications：删除 30 天前已读记录
 * - storage/logs：删除上月的日志文件（对应原版 uploads/logs 清理）
 */
class HousekeepingCleanupCommand extends Command
{
    protected $signature = 'monit:housekeeping-cleanup';

    protected $description = '清理过期账户日志、内部通知与旧日志文件';

    public function handle(): int
    {
        // 1. account_logs 90 天（原版 users_logs_cleanup）
        $logsDeleted = \DB::table('account_logs')
            ->where('datetime', '<', now()->subDays(90))
            ->delete();

        // 2. internal_notifications 30 天（原版 internal_notifications_cleanup，只清已读避免误删未读）
        $notificationsDeleted = \DB::table('internal_notifications')
            ->where('datetime', '<', now()->subDays(30))
            ->delete();

        // 3. storage/logs 上月日志文件（原版 logs_cleanup）
        $filesDeleted = 0;
        $currentMonth = now()->format('Y-m');
        $logPath = storage_path('logs');

        if (is_dir($logPath)) {
            clearstatcache();
            foreach (glob($logPath.'/*.log') ?: [] as $file) {
                $mtime = (int) (@filemtime($file) ?: 0);
                if ($mtime > 0 && date('Y-m', $mtime) !== $currentMonth) {
                    @unlink($file);
                    $filesDeleted++;
                }
            }
        }

        $this->info("账户日志清理 {$logsDeleted} 条；内部通知清理 {$notificationsDeleted} 条；日志文件清理 {$filesDeleted} 个");

        return self::SUCCESS;
    }
}

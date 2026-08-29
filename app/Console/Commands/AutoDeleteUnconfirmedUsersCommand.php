<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 自动删除未确认用户 Cron
 * 规格书 §13：每日检查 settings()->users->auto_delete_unconfirmed_users，
 * status=0 且 datetime<过期天数前 → User::delete()，LIMIT 100
 */
class AutoDeleteUnconfirmedUsersCommand extends Command
{
    protected $signature = 'monit:auto-delete-unconfirmed-users';

    protected $description = '自动删除未激活的过期用户';

    public function handle(): int
    {
        // 从 settings 读取配置
        $settingsValue = DB::table('settings')
            ->where('key', 'main.auto_delete_unconfirmed_users')
            ->value('value');

        if (! $settingsValue) {
            $this->info('自动删除未确认用户功能未启用');

            return self::SUCCESS;
        }

        $days = (int) ($settingsValue ?? 3);
        if ($days < 1) {
            $days = 3;
        }

        $cutoffDate = now()->subDays($days);

        $deleted = User::where('status', 0)
            ->where('created_at', '<', $cutoffDate)
            ->limit(100)
            ->get();

        $count = 0;
        foreach ($deleted as $user) {
            $user->delete();
            $count++;
        }

        $this->info("已删除 {$count} 个未确认用户（超过 {$days} 天）");

        return self::SUCCESS;
    }
}

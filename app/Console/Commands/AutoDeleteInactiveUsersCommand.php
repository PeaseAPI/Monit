<?php

namespace App\Console\Commands;

use App\Mail\AutoDeleteInactiveUsers;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * 自动删除不活跃用户 Cron（原版 auto_delete_inactive_users，规格书 §13.1 M22）
 * 删除已被提醒过（user_deletion_reminder=1）且超过 inactivity 天数的免费用户
 */
class AutoDeleteInactiveUsersCommand extends Command
{
    protected $signature = 'monit:auto-delete-inactive-users';

    protected $description = '自动删除超过不活跃期限的免费用户';

    public function handle(): int
    {
        $days = (int) (DB::table('settings')->where('key', 'auto_delete_inactive_users')->value('value') ?: 0);

        if ($days < 1) {
            $this->info('自动删除不活跃用户功能未启用');

            return self::SUCCESS;
        }

        $pastDate = now()->subDays($days);

        $users = User::where('plan_id', 'free')
            ->where('type', 0)
            ->where('user_deletion_reminder', true)
            ->where(function ($q) use ($pastDate) {
                $q->where('last_activity', '<', $pastDate)
                    ->orWhereNull('last_activity');
            })
            ->limit(25)
            ->get();

        $deleted = 0;
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new AutoDeleteInactiveUsers($user, $days));
            } catch (\Throwable $e) {
                // 邮件失败不阻断删除
            }

            $user->delete();
            $deleted++;
        }

        $this->info("已删除 {$deleted} 个不活跃用户（超过 {$days} 天）");

        return self::SUCCESS;
    }
}

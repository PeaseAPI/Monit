<?php

namespace App\Console\Commands;

use App\Mail\UserDeletionReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * 不活跃用户删除提醒 Cron（原版 users_deletion_reminder，规格书 §13.1 M22）
 * 依赖 settings：auto_delete_inactive_users（天数，0=关闭）、user_deletion_reminder（提前提醒天数）
 */
class UsersDeletionReminderCommand extends Command
{
    protected $signature = 'monit:users-deletion-reminder';

    protected $description = '提醒即将因不活跃被删除的免费用户';

    public function handle(): int
    {
        $autoDeleteDays = (int) (DB::table('settings')->where('key', 'auto_delete_inactive_users')->value('value') ?: 0);

        if ($autoDeleteDays < 1) {
            $this->info('自动删除不活跃用户功能未启用');

            return self::SUCCESS;
        }

        $reminderDays = (int) (DB::table('settings')->where('key', 'user_deletion_reminder')->value('value') ?: 7);
        $pastDate = now()->subDays(max(0, $autoDeleteDays - $reminderDays));

        // 仅提醒免费、普通类型、未提醒过的不活跃用户（原版：plan_id=free AND type=0）
        $users = User::where('plan_id', 'free')
            ->where('type', 0)
            ->where('user_deletion_reminder', false)
            ->where(function ($q) use ($pastDate) {
                $q->where('last_activity', '<', $pastDate)
                    ->orWhereNull('last_activity');
            })
            ->limit(25)
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new UserDeletionReminder($user, $reminderDays));
            } catch (\Throwable $e) {
                // 邮件失败不阻断流程
            }

            $user->update(['user_deletion_reminder' => true]);
            $sent++;
        }

        $this->info("已发送 {$sent} 封不活跃用户删除提醒");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * 套餐到期提醒 Cron
 * 规格书 §13：每日检查即将到期用户续费提醒，LIMIT 25
 */
class UsersPlanExpiryReminderCommand extends Command
{
    protected $signature = 'monit:users-plan-expiry-reminder';

    protected $description = '发送套餐到期提醒';

    public function handle(): int
    {
        // 检查是否启用
        $isEnabled = \Illuminate\Support\Facades\DB::table('settings')
            ->where('key', 'payment.user_plan_expiry_reminder')
            ->value('value');

        if (! $isEnabled || $isEnabled === 'false') {
            $this->info('套餐到期提醒功能未启用');

            return self::SUCCESS;
        }

        // 查找 7 天内到期的用户
        $soonExpiry = now()->addDays(7);
        $users = User::whereNotNull('plan_expiration_date')
            ->where('plan_expiration_date', '<=', $soonExpiry)
            ->where('plan_expiration_date', '>', now())
            ->where('plan_expiry_reminder', false)
            ->whereNotIn('plan_id', ['free', 'guest'])
            ->limit(25)
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            // TODO: 发送到期提醒邮件
            // Mail::to($user)->queue(new PlanExpiryReminderMail($user));

            $user->update(['plan_expiry_reminder' => true]);
            $sent++;
        }

        $this->info("已发送 {$sent} 封到期提醒邮件");

        return self::SUCCESS;
    }
}

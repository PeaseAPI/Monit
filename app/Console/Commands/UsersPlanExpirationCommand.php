<?php

namespace App\Console\Commands;

use App\Mail\PlanDowngraded;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * 套餐过期降级 Cron
 * 规格书 §13：每小时遍历 plan_expiration_date<now 的用户，降级 free，清空订阅信息
 */
class UsersPlanExpirationCommand extends Command
{
    protected $signature = 'monit:users-plan-expiration';

    protected $description = '降级过期套餐用户为 free';

    public function handle(): int
    {
        $now = now();
        $expiredCount = 0;

        // 查找已过期且非 free/guest 套餐的用户
        $users = User::whereNotNull('plan_expiration_date')
            ->where('plan_expiration_date', '<', $now)
            ->whereNotIn('plan_id', ['free', 'guest'])
            ->limit(500)
            ->get();

        foreach ($users as $user) {
            $user->forceFill([
                'plan_id' => 'free',
                'plan_expiration_date' => null,
                'plan_settings' => null,
                'payment_subscription_id' => null,
                'payment_processor' => null,
                'payment_total_amount' => null,
                'payment_currency' => null,
                'plan_expiry_reminder' => false,
            ])->save();

            // 降级通知（规格 §13.1：降级后通知用户）
            Mail::to($user->email)->queue(new PlanDowngraded($user));

            $expiredCount++;
        }

        $this->info("已降级 {$expiredCount} 个过期套餐用户");

        return self::SUCCESS;
    }
}

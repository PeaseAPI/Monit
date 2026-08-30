<?php

namespace App\Console\Commands;

use App\Mail\PlanLimitNotice;
use App\Models\User;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * 站点配额超限通知 Cron（原版 websites_sessions_events_notice / events_children_notice / sessions_replays_notice 三合一，规格书 §13.1 M22）
 * 依赖 settings：email_notices_is_enabled
 * 站点 current_month_* 超过套餐限额且未提醒过 → 邮件 + plan_*_limit_notice=1
 */
class WebsitesLimitNoticeCommand extends Command
{
    protected $signature = 'monit:websites-limit-notice';

    protected $description = '站点配额超限时发送邮件通知（事件/事件子项/回放）';

    /** 配额映射：月度计数列 => 通知标志列 + 套餐功能键 + 邮件场景 */
    protected const QUOTAS = [
        'current_month_sessions_events' => [
            'flag' => 'plan_sessions_events_limit_notice',
            'feature' => 'sessions_events_limit',
            'scene' => 'sessions_events',
        ],
        'current_month_events_children' => [
            'flag' => 'plan_events_children_limit_notice',
            'feature' => 'events_children_limit',
            'scene' => 'events_children',
        ],
        'current_month_sessions_replays' => [
            'flag' => 'plan_sessions_replays_limit_notice',
            'feature' => 'sessions_replays_limit',
            'scene' => 'sessions_replays',
        ],
    ];

    public function handle(): int
    {
        $enabled = DB::table('settings')->where('key', 'email_notices_is_enabled')->value('value');

        if (! $enabled || $enabled === 'false') {
            $this->info('配额通知邮件功能未启用');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach (static::QUOTAS as $counter => $meta) {
            // 超限且未提醒的站点（LIMIT 25，对齐原版；套餐限额来自用户 plan_settings，PHP 内比对）
            $websites = Website::query()
                ->where($meta['flag'], false)
                ->where($counter, '>', 0)
                ->limit(25)
                ->get();

            foreach ($websites as $website) {
                /** @var User|null $owner */
                $owner = User::find($website->user_id);
                if (! $owner || $owner->status !== 1) {
                    continue;
                }

                $limit = (int) ($owner->getPlanSettings()[$meta['feature']] ?? 0);

                // -1 = 不限；未超限跳过
                if ($limit === -1 || $website->{$counter} < $limit) {
                    continue;
                }

                try {
                    Mail::to($owner->email)->queue(new PlanLimitNotice(
                        $owner,
                        $website,
                        $meta['scene'],
                        $limit,
                        (int) $website->{$counter}
                    ));
                } catch (\Throwable $e) {
                    // 邮件失败不阻断
                }

                $website->update([$meta['flag'] => true]);
                $sent++;
            }
        }

        $this->info("已发送 {$sent} 封配额超限通知");

        return self::SUCCESS;
    }
}

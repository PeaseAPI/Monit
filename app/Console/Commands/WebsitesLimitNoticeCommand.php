<?php

namespace App\Console\Commands;

use App\Mail\PlanLimitNotice;
use App\Models\User;
use App\Models\Website;
use App\Support\Typed;
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
    /**
     * 配额定义：counter（用量列）=> flag（已提醒标记）/ feature（限额键）/ scene（邮件场景）
     *
     * @var array<string, array{flag: string, feature: string, scene: string}>
     */
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

                // 缺键 ?? -1 = 不限（custom 套餐 plan_settings 不与 plan_defaults 合并，
                // 对齐采集侧 insertEventChild / persistReplayChunk 的缺键语义）；
                // 原 ?? 0 会把缺键用户判成「限额 0」→ 任何用量都误发超限邮件
                $limit = Typed::int($owner->getPlanSettings()[$meta['feature']] ?? -1);

                // -1 = 不限；0 = 功能禁用（与采集侧 0 不标记一致——用户没有此功能，
                // 发「配额超限」邮件反而误导其升级；生产 Plus 套餐 sessions_replays_limit=0）；
                // 未超限跳过
                if ($limit === -1 || $limit === 0 || $website->{$counter} < $limit) {
                    continue;
                }

                try {
                    Mail::to($owner->email)->queue(new PlanLimitNotice(
                        $owner,
                        $website,
                        Typed::string($meta['scene']),
                        $limit,
                        Typed::int($website->{$counter})
                    ));

                    // 标志必须在邮件成功入队后才置位：queue 抛异常（队列连接故障等）
                    // 时不标记，下一轮 Cron 重试，避免「已标记但从未发出」的通知静默丢失
                    // （修复前 update 在 try 外无条件执行，队列挂掉即通知丢失整月）
                    $website->update([$meta['flag'] => true]);
                    $sent++;
                } catch (\Throwable $e) {
                    // 邮件失败不阻断本轮其他站点，下轮重试
                }
            }
        }

        $this->info("已发送 {$sent} 封配额超限通知");

        return self::SUCCESS;
    }
}

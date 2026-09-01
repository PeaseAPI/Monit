<?php

namespace App\Http\Controllers;

use App\Models\SessionReplay;
use App\Models\User;
use App\Models\VisitorSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 定时任务端点（规格书 §13）
 * Laravel Scheduler 内部触发，无需外部 HTTP 调用
 * 但保留 HTTP 端点供手动触发 / 兼容宝塔面板 cron
 */
class CronController extends Controller
{
    /**
     * Cron 鉴权（fail-closed）：key 未配置（null/空）一律拒绝；
     * hash_equals 常时比较防时序侧信道
     * key 来源：settings cron.cron_key（后台可改）→ config('app.cron_key') 兜底
     */
    protected function authorized(Request $request): bool
    {
        $expected = trim((string) \App\Support\Settings::get('cron.cron_key', '')) ?: (string) config('app.cron_key');

        if ($expected === '') {
            return false;
        }

        $provided = (string) $request->query('key', '');

        return $provided !== '' && hash_equals($expected, $provided);
    }

    /**
     * 主 Cron 入口（规格书 §13：/cron）
     * 需要 ?key=CRON_KEY 鉴权
     */
    public function index(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Invalid cron key'], 403);
        }

        // Webhook：cron 开始（webhooks.webhooks_cron_start）
        app(\App\Services\WebhookService::class)->cronStart();

        $results = [];
        $results['users_plan_expiration'] = $this->usersPlanExpiration();
        $results['auto_delete_unconfirmed'] = $this->autoDeleteUnconfirmedUsers();
        $results['websites_replays_cleanup'] = $this->websitesReplaysCleanup();
        $results['analytics_cleanup'] = $this->analyticsCleanup();
        $results['users_plan_expiry_reminder'] = $this->usersPlanExpiryReminder();
        $results['broadcasts'] = $this->broadcasts();
        $results['email_reports'] = $this->emailReports();

        // Webhook：cron 结束（webhooks.webhooks_cron_end）
        app(\App\Services\WebhookService::class)->cronEnd($results);

        return response()->json(['status' => 'ok', 'results' => $results]);
    }

    /**
     * 子任务入口（规格 §13.1：/cron/email_reports、/cron/broadcasts、/cron/push_notifications）
     * 供外部调度器（宝塔/cron-tab）按不同频率分别调用
     */
    public function task(Request $request, string $task): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Invalid cron key'], 403);
        }

        // 子任务开关（cron.cron_email_reports / cron_broadcasts / cron_push_notifications，默认开启）
        $enabled = match ($task) {
            'email_reports' => $this->cronTaskOn('cron_email_reports'),
            'broadcasts' => $this->cronTaskOn('cron_broadcasts'),
            'push_notifications' => $this->cronTaskOn('cron_push_notifications'),
            default => true,
        };

        if (! $enabled) {
            return response()->json(['status' => 'skipped', 'task' => $task, 'reason' => 'disabled']);
        }

        $result = match ($task) {
            'email_reports' => $this->emailReports(),
            'broadcasts' => $this->broadcasts(),
            'push_notifications' => Artisan::call('monit:push-notifications-campaigns'),
            default => null,
        };

        if ($result === null) {
            return response()->json(['error' => 'Unknown task'], 404);
        }

        return response()->json(['status' => 'ok', 'task' => $task, 'result' => $result]);
    }

    /**
     * cron 子任务开关（settings cron 组，默认开启）
     */
    protected function cronTaskOn(string $key): bool
    {
        $value = \App\Support\Settings::get('cron.'.$key);

        return $value === null || in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    /**
     * 套餐过期降级（规格书 §13.1：users_plan_expiration）
     */
    protected function usersPlanExpiration(): int
    {
        $count = 0;
        User::where('plan_expiration_date', '<', now())
            ->where('plan_id', '!=', 'free')
            ->limit(100)
            ->each(function (User $user) use (&$count): void {
                $user->update([
                    'plan_id' => 'free',
                    'plan_expiration_date' => null,
                    'payment_subscription_id' => null,
                    'payment_processor' => null,
                ]);
                $count++;
            });

        return $count;
    }

    /**
     * 自动删除未确认用户（规格书 §13.1）
     */
    protected function autoDeleteUnconfirmedUsers(): int
    {
        $days = (int) config('app.auto_delete_unconfirmed_days', 3);
        $count = 0;
        User::where('status', 0)
            ->where('created_at', '<', now()->subDays($days))
            ->limit(100)
            ->each(function (User $user) use (&$count): void {
                $user->delete();
                $count++;
            });

        return $count;
    }

    /**
     * 清理过期回放（规格书 §13.1）
     */
    protected function websitesReplaysCleanup(): int
    {
        $retentionDays = (int) config('app.replays_retention_days', 30);
        $count = 0;
        // sessions_replays 无 created_at（timestamps=false），过期判定用 datetime 列
        SessionReplay::where('datetime', '<', now()->subDays($retentionDays))
            ->limit(30)
            ->each(function ($replay) use (&$count): void {
                $replay->delete();
                $count++;
            });

        return $count;
    }

    /**
     * 清理过期分析数据（规格书 §13.1）
     */
    protected function analyticsCleanup(): int
    {
        $retentionDays = (int) config('app.analytics_retention_days', 365);
        $count = 0;
        // 清理过期会话事件
        $count += VisitorSession::where('date', '<', now()->subDays($retentionDays))->limit(500)->delete();

        return $count;
    }

    /**
     * 套餐过期提醒（规格书 §13.1）——委托 artisan 命令（真实发送提醒邮件）
     */
    protected function usersPlanExpiryReminder(): int
    {
        return (int) Artisan::call('monit:users-plan-expiry-reminder');
    }

    /**
     * 发送待发送广播邮件（规格书 §13.1）——委托 artisan 命令（逐收件人异步派发）
     */
    protected function broadcasts(): int
    {
        return (int) Artisan::call('monit:process-broadcasts');
    }

    /**
     * 邮件报表（规格书 §13.1）——委托 artisan 命令
     */
    protected function emailReports(): int
    {
        return (int) Artisan::call('monit:send-email-reports');
    }
}

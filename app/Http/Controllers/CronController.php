<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Website;
use App\Models\VisitorSession;
use App\Models\SessionReplay;
use App\Models\Broadcast;
use App\Jobs\SendBroadcastEmail;
use App\Jobs\SendEmailReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 定时任务端点（规格书 §13）
 * Laravel Scheduler 内部触发，无需外部 HTTP 调用
 * 但保留 HTTP 端点供手动触发 / 兼容宝塔面板 cron
 */
class CronController extends Controller
{
    /**
     * 主 Cron 入口（规格书 §13：/cron）
     * 需要 ?key=CRON_KEY 鉴权
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->query('key') !== config('app.cron_key')) {
            return response()->json(['error' => 'Invalid cron key'], 403);
        }

        $results = [];
        $results['users_plan_expiration'] = $this->usersPlanExpiration();
        $results['auto_delete_unconfirmed'] = $this->autoDeleteUnconfirmedUsers();
        $results['websites_replays_cleanup'] = $this->websitesReplaysCleanup();
        $results['analytics_cleanup'] = $this->analyticsCleanup();
        $results['users_plan_expiry_reminder'] = $this->usersPlanExpiryReminder();
        $results['broadcasts'] = $this->broadcasts();
        $results['email_reports'] = $this->emailReports();

        return response()->json(['status' => 'ok', 'results' => $results]);
    }

    /**
     * 子任务入口（规格 §13.1：/cron/email_reports、/cron/broadcasts、/cron/push_notifications）
     * 供外部调度器（宝塔/cron-tab）按不同频率分别调用
     */
    public function task(Request $request, string $task): JsonResponse
    {
        if ($request->query('key') !== config('app.cron_key')) {
            return response()->json(['error' => 'Invalid cron key'], 403);
        }

        $result = match ($task) {
            'email_reports' => $this->emailReports(),
            'broadcasts' => $this->broadcasts(),
            'push_notifications' => \Illuminate\Support\Facades\Artisan::call('monit:push-notifications-campaigns'),
            default => null,
        };

        if ($result === null) {
            return response()->json(['error' => 'Unknown task'], 404);
        }

        return response()->json(['status' => 'ok', 'task' => $task, 'result' => $result]);
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
        SessionReplay::where('created_at', '<', now()->subDays($retentionDays))
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
     * 套餐过期提醒（规格书 §13.1）
     */
    protected function usersPlanExpiryReminder(): int
    {
        $days = (int) config('app.plan_expiry_reminder_days', 3);
        $count = 0;
        User::where('plan_expiration_date', '>=', now())
            ->where('plan_expiration_date', '<=', now()->addDays($days))
            ->where('plan_expiry_reminder', false)
            ->where('plan_id', '!=', 'free')
            ->limit(25)
            ->each(function (User $user) use (&$count): void {
                // 发送提醒邮件（实际实现用 Mail facade）
                $user->update(['plan_expiry_reminder' => true]);
                $count++;
            });
        return $count;
    }

        /**
     * 发送待发送广播邮件（规格书 §13.1）
     */
    protected function broadcasts(): int
    {
        $count = 0;
        Broadcast::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->limit(25)
            ->each(function (Broadcast $broadcast) use (&$count): void {
                $broadcast->update(['status' => 'sending']);
                SendBroadcastEmail::dispatch($broadcast);
                $count++;
            });
        return $count;
    }

    /**
     * 邮件报表（规格书 §13.1）
     */
    protected function emailReports(): int
    {
        $count = 0;
        Website::where('email_reports_is_enabled', 1)
            ->where(function ($q) {
                $q->whereNull('email_reports_last_date')
                    ->orWhere('email_reports_last_date', '<', now()->subWeek());
            })
            ->limit(25)
            ->each(function (Website $website) use (&$count): void {
                SendEmailReport::dispatch($website);
                $website->update(['email_reports_last_date' => now()]);
                $count++;
            });
        return $count;
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Website;

/**
 * 套餐限额检查服务
 * 规格书 §10.2：25 个可售功能项限制检查
 */
class PlanLimitService
{
    /**
     * 检查用户是否超过某项限额
     */
    public function checkLimit(User $user, string $feature, int $increment = 1): bool
    {
        $settings = $user->getPlanSettings();
        $limit = $settings[$feature] ?? 0;

        // -1 表示不限；0 表示功能未启用（由 isFeatureEnabled 拦截）
        if ($limit === -1) {
            return true;
        }

        if ($limit <= 0) {
            return false;
        }

        $current = $this->getCurrentUsage($user, $feature);

        return ($current + $increment) <= $limit;
    }

    /**
     * 获取当前使用量
     */
    public function getCurrentUsage(User $user, string $feature): int
    {
        return match ($feature) {
            'websites_limit' => $user->websites()->count(),
            'websites_heatmaps_limit' => $this->getWebsitesAggregate($user, 'heatmaps'),
            'websites_goals_limit' => $this->getWebsitesAggregate($user, 'goals'),
            'annotations_limit' => $user->annotations()->count(),
            'domains_limit' => $user->domains()->count(),
            'dashboard_views_limit' => $user->dashboardViews()->count(),
            default => 0,
        };
    }

    /**
     * 获取限额剩余量
     */
    public function getRemaining(User $user, string $feature): int
    {
        $settings = $user->getPlanSettings();
        $limit = $settings[$feature] ?? 0;

        if ($limit === -1) {
            return -1; // 不限
        }

        if ($limit <= 0) {
            return 0; // 未启用
        }

        return max(0, $limit - $this->getCurrentUsage($user, $feature));
    }

    /**
     * 检查功能是否启用
     */
    public function isFeatureEnabled(User $user, string $feature): bool
    {
        $settings = $user->getPlanSettings();

        return (bool) ($settings[$feature] ?? false);
    }

    /**
     * 检查网站月度事件配额
     */
    public function checkMonthlyEventsQuota(Website $website): bool
    {
        $user = $website->user;
        $settings = $user->getPlanSettings();
        $limit = $settings['sessions_events_limit'] ?? 0;

        if ($limit <= 0) {
            return true;
        }

        return $website->current_month_sessions_events < $limit;
    }

    /**
     * 检查网站月度回放配额
     */
    public function checkMonthlyReplaysQuota(Website $website): bool
    {
        $user = $website->user;
        $settings = $user->getPlanSettings();
        $limit = $settings['sessions_replays_limit'] ?? 0;

        if ($limit <= 0) {
            return true;
        }

        return $website->current_month_sessions_replays < $limit;
    }

    /**
     * 聚合网站子资源计数
     */
    protected function getWebsitesAggregate(User $user, string $relation): int
    {
        return $user->websites()->withCount($relation)->get()->sum("{$relation}_count");
    }
}

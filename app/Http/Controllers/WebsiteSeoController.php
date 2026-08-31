<?php

namespace App\Http\Controllers;

use App\Models\SeoToolUse;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 网站详情 SEO 标签页（融合方案 §8.1：/websites/{website}/seo）
 * - 该站审计历史 + 聚合（seo_avg_score / seo_total_audits）
 * - 定时复审 / 通知 / Sitemap 监控设置（写 seo_* 列）
 */
class WebsiteSeoController extends Controller
{
    protected const INTERVALS = ['never', 'daily', 'weekly', 'monthly', 'every3months', 'every6months', 'yearly'];

    public function show(Request $request, Website $website): View
    {
        $audits = $website->seoAudits()->orderByDesc('seo_audit_id')->paginate(10);

        return view('seo.website', [
            'website' => $website,
            'audits' => $audits,
            'intervals' => self::INTERVALS,
            'topTools' => SeoToolUse::topTools(5),
        ]);
    }

    /**
     * 更新 SEO 监控设置（复审排期即时重算）
     */
    public function update(Request $request, Website $website)
    {
        $validated = $request->validate([
            'seo_audit_check_interval' => 'required|in:'.implode(',', self::INTERVALS),
            'seo_notifications_enabled' => 'nullable|boolean',
            'seo_notifications_mode' => 'nullable|in:always,changes',
            'seo_sitemap_url' => 'nullable|url|max:512',
            'seo_sitemap_check_interval' => 'nullable|in:never,daily,weekly,monthly',
        ]);

        $website->update([
            'seo_audit_check_interval' => $validated['seo_audit_check_interval'],
            'seo_notifications_enabled' => (bool) ($validated['seo_notifications_enabled'] ?? false),
            'seo_notifications_mode' => $validated['seo_notifications_mode'] ?? 'always',
            'seo_sitemap_url' => $validated['seo_sitemap_url'] ?? null,
            'seo_sitemap_check_interval' => $validated['seo_sitemap_check_interval'] ?? 'never',
            'seo_next_audit_at' => $this->nextRunAt($validated['seo_audit_check_interval']),
        ]);

        return back()->with('success', __('seo.saved'));
    }

    protected function nextRunAt(string $interval): ?string
    {
        $next = match ($interval) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'every3months' => now()->addMonths(3),
            'every6months' => now()->addMonths(6),
            'yearly' => now()->addYear(),
            default => null,
        };

        return $next?->format('Y-m-d H:i:s');
    }
}

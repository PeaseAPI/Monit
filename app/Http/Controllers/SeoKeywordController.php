<?php

namespace App\Http\Controllers;

use App\Models\SeoKeyword;
use App\Models\Website;
use App\Services\PlanLimitService;
use App\Services\Seo\RankTracker;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * 关键词排名跟踪控制器（SEO模块融合方案 §8 扩展）
 * - 自动查 SERP：后台配置 seo.serpapi_api_key 后启用
 * - 手动快照：无 API 场景下录入排名，平台负责趋势
 */
class SeoKeywordController extends Controller
{
    public function index(Request $request): View
    {
        $keywords = SeoKeyword::with('website')
            ->where('user_id', $request->user()->user_id)
            ->when($request->filled('website'), fn ($q) => $q->where('website_id', (int) $request->query('website')))
            ->orderByDesc('seo_keyword_id')
            ->paginate(20)
            ->withQueryString();

        $all = SeoKeyword::where('user_id', $request->user()->user_id)->whereNotNull('last_position')->get();

        $summary = [
            'tracked' => SeoKeyword::where('user_id', $request->user()->user_id)->count(),
            'top3' => $all->where('last_position', '<=', 3)->count(),
            'top10' => $all->where('last_position', '<=', 10)->count(),
            'top100' => $all->where('last_position', '<=', 100)->count(),
            'avg' => $all->isNotEmpty() ? (int) round($all->avg('last_position')) : null,
        ];

        return view('seo.keywords', [
            'keywords' => $keywords,
            'summary' => $summary,
            'websites' => Website::where('user_id', $request->user()->user_id)->orderBy('host')->get(['website_id', 'host']),
            'autoEnabled' => RankTracker::configured(),
        ]);
    }

    /**
     * 添加跟踪关键词（套餐 seo_keywords_limit 配额）
     */
    public function store(Request $request, PlanLimitService $limits)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:256',
            'website_id' => 'nullable|integer|exists:websites,website_id',
            'search_engine' => 'nullable|in:google,bing,baidu',
            'device' => 'nullable|in:desktop,mobile',
            'locale' => 'nullable|string|max:16',
            'target_url' => 'nullable|url|max:2048',
            'check_interval' => 'nullable|in:never,daily,weekly,monthly',
        ]);

        $validated['website_id'] = $this->ownWebsiteId($request, (int) ($validated['website_id'] ?? 0));

        if (! $limits->checkLimit($request->user(), 'seo_keywords_limit')) {
            return back()->withErrors(['keyword' => __('seo.keywords_quota_exceeded')]);
        }

        $exists = SeoKeyword::where('user_id', $request->user()->user_id)
            ->where('keyword', $validated['keyword'])
            ->where('search_engine', $validated['search_engine'] ?? 'google')
            ->where('device', $validated['device'] ?? 'desktop')
            ->where('locale', $validated['locale'] ?? 'zh-CN')
            ->exists();

        if ($exists) {
            return back()->withErrors(['keyword' => __('seo.keyword_exists')]);
        }

        SeoKeyword::create([
            'user_id' => $request->user()->user_id,
            'website_id' => $validated['website_id'],
            'keyword' => trim($validated['keyword']),
            'search_engine' => $validated['search_engine'] ?? 'google',
            'device' => $validated['device'] ?? 'desktop',
            'locale' => $validated['locale'] ?? 'zh-CN',
            'target_url' => $validated['target_url'] ?? null,
            'check_interval' => $validated['check_interval'] ?? 'weekly',
            'is_enabled' => true,
        ]);

        return back()->with('success', __('seo.keyword_added'));
    }

    /**
     * 手动录入排名快照
     */
    public function snapshot(Request $request, SeoKeyword $keyword, RankTracker $tracker)
    {
        $this->authorizeOwn($request, $keyword);

        $validated = $request->validate([
            'position' => 'nullable|integer|min:1|max:1000',
        ]);

        $tracker->record($keyword, $validated['position'] ?? null, null, 'manual');

        return back()->with('success', __('seo.snapshot_saved'));
    }

    /**
     * 立即刷新排名（需 SerpApi 已配置）
     */
    public function refresh(Request $request, SeoKeyword $keyword, RankTracker $tracker)
    {
        $this->authorizeOwn($request, $keyword);

        if (! RankTracker::configured()) {
            return back()->withErrors(['keyword' => __('seo.serpapi_not_configured')]);
        }

        try {
            $rank = $tracker->check($keyword);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['keyword' => __('seo.rank_check_failed')]);
        }

        return back()->with('success', __('seo.rank_checked', ['position' => $rank->position ?? __('seo.not_ranked')]));
    }

    /**
     * 更新跟踪设置（开关 / 间隔）
     */
    public function update(Request $request, SeoKeyword $keyword)
    {
        $this->authorizeOwn($request, $keyword);

        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'check_interval' => 'nullable|in:never,daily,weekly,monthly',
        ]);

        $keyword->update([
            'is_enabled' => (bool) ($validated['is_enabled'] ?? $keyword->is_enabled),
            'check_interval' => $validated['check_interval'] ?? $keyword->check_interval,
        ]);

        return back()->with('success', __('seo.keyword_updated'));
    }

    public function destroy(Request $request, SeoKeyword $keyword)
    {
        $this->authorizeOwn($request, $keyword);

        $keyword->ranks()->delete();
        $keyword->delete();

        return back()->with('success', __('seo.keyword_deleted'));
    }

    protected function ownWebsiteId(Request $request, int $websiteId): ?int
    {
        if ($websiteId <= 0) {
            return null;
        }

        $owned = Website::where('user_id', $request->user()->user_id)->where('website_id', $websiteId)->value('website_id');

        return $owned ? (int) $owned : null;
    }

    protected function authorizeOwn(Request $request, SeoKeyword $keyword): void
    {
        if ((int) $keyword->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }
    }
}

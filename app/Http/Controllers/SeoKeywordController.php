<?php

namespace App\Http\Controllers;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordRank;
use App\Models\Website;
use App\Services\PlanLimitService;
use App\Services\Seo\RankTracker;
use App\Support\Typed;
use Illuminate\Http\RedirectResponse;
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
            ->where('user_id', $this->user()->user_id)
            ->when($request->filled('website'), fn ($q) => $q->where('website_id', (int) $request->query('website')))
            ->orderByDesc('seo_keyword_id')
            ->paginate(20)
            ->withQueryString();

        // M30：当前页关键词的最近排名快照（行内展开历史趋势，「监控中」不再无处可看）
        $recentRanks = SeoKeywordRank::whereIn('seo_keyword_id', $keywords->getCollection()->pluck('seo_keyword_id'))
            ->orderByDesc('checked_at')
            ->limit(count($keywords) * 10 + 10)
            ->get()
            ->groupBy('seo_keyword_id')
            ->map(fn ($group) => $group->take(10));

        $all = SeoKeyword::where('user_id', $this->user()->user_id)->whereNotNull('last_position')->get();

        $summary = [
            'tracked' => SeoKeyword::where('user_id', $this->user()->user_id)->count(),
            'top3' => $all->where('last_position', '<=', 3)->count(),
            'top10' => $all->where('last_position', '<=', 10)->count(),
            'top100' => $all->where('last_position', '<=', 100)->count(),
            'avg' => $all->isNotEmpty() ? (int) round((float) $all->avg('last_position')) : null,
        ];

        return view('seo.keywords', [
            'keywords' => $keywords,
            'summary' => $summary,
            'websites' => Website::where('user_id', $this->user()->user_id)->orderBy('host')->get(['website_id', 'host']),
            // 任务 #36-7：自动查询不再依赖 SerpApi（内置 Bing/百度抓取兜底恒可用），
            // serpConfigured 仅用于信息性提示（配置 SerpApi 可增加 Google 支持）
            'autoEnabled' => true,
            'serpConfigured' => RankTracker::configured(),
            'recentRanks' => $recentRanks,
        ]);
    }

    /**
     * 添加跟踪关键词（套餐 seo_keywords_limit 配额）
     *
     * @return RedirectResponse
     */
    public function store(Request $request, PlanLimitService $limits)
    {
        $validated = Typed::arr($request->validate([
            'keyword' => 'required|string|max:256',
            'website_id' => 'nullable|integer|exists:websites,website_id',
            'search_engine' => 'nullable|in:google,bing,baidu',
            'device' => 'nullable|in:desktop,mobile',
            'locale' => 'nullable|string|max:16',
            'target_url' => 'nullable|url|max:2048',
            'check_interval' => 'nullable|in:never,daily,weekly,monthly',
        ]));

        $validated['website_id'] = $this->ownWebsiteId($request, Typed::int($validated['website_id'] ?? 0));

        // 任务 #35-7：无网站流程 —— 不关联网站的关键词必须给目标 URL，
        // 否则 SERP 结果没有可匹配的 host（自动检查只会得到「未找到」）
        if (Typed::int($validated['website_id']) <= 0 && trim(Typed::string($validated['target_url'] ?? '')) === '') {
            return back()
                ->withErrors(['target_url' => __('seo.target_host_required')])
                ->withInput();
        }

        if (! $limits->checkLimit($this->user(), 'seo_keywords_limit')) {
            return back()->withErrors(['keyword' => __('seo.keywords_quota_exceeded')]);
        }

        $exists = SeoKeyword::where('user_id', $this->user()->user_id)
            ->where('keyword', $validated['keyword'])
            ->where('search_engine', $validated['search_engine'] ?? 'google')
            ->where('device', $validated['device'] ?? 'desktop')
            ->where('locale', $validated['locale'] ?? 'zh-CN')
            ->exists();

        if ($exists) {
            return back()->withErrors(['keyword' => __('seo.keyword_exists')]);
        }

        $keyword = SeoKeyword::create([
            'user_id' => $this->user()->user_id,
            'website_id' => $validated['website_id'],
            'keyword' => trim(Typed::string($validated['keyword'])),
            'search_engine' => $validated['search_engine'] ?? 'google',
            'device' => $validated['device'] ?? 'desktop',
            'locale' => $validated['locale'] ?? 'zh-CN',
            'target_url' => $validated['target_url'] ?? null,
            'check_interval' => $validated['check_interval'] ?? 'weekly',
            'is_enabled' => true,
        ]);

        // 用户反馈 #36-7：首次添加立即自动查询一次——SerpApi 已配置走官方 API，
        // 未配置走内置 Bing/百度抓取兜底（不再依赖第三方服务才能出首条快照）。
        // 失败不影响添加流程（cron 每小时扫描 /「立即刷新」可补齐），但给出
        // 明确提示而非静默成功，避免用户以为已出排名。
        try {
            app(RankTracker::class)->check($keyword);
        } catch (Throwable $e) {
            report($e);

            return back()->with('success', __('seo.keyword_added_pending'));
        }

        return back()->with('success', __('seo.keyword_added'));
    }

    /**
     * 手动录入排名快照
     *
     * @return RedirectResponse
     */
    public function snapshot(Request $request, SeoKeyword $keyword, RankTracker $tracker)
    {
        $this->authorizeOwn($request, $keyword);

        $validated = Typed::arr($request->validate([
            'position' => 'nullable|integer|min:1|max:1000',
        ]));

        $tracker->record($keyword, Typed::intOrNull($validated['position'] ?? null), null, 'manual');

        return back()->with('success', __('seo.snapshot_saved'));
    }

    /**
     * 排名查询失败的用户文案：按异常原因分类（百度反爬 / Google 不可达 / 通用），
     * 给出可行出路（换 Bing 引擎 / 配置 SerpApi / 手动录入）而非笼统的「稍后重试」
     */
    protected function rankFailureMessage(Throwable $e): string
    {
        $reason = $e->getMessage();

        if (str_starts_with($reason, 'serp_scrape_blocked:baidu')) {
            return __('seo.rank_blocked_baidu');
        }

        if (str_starts_with($reason, 'serp_scrape_unreachable:google')) {
            return __('seo.rank_unreachable_google');
        }

        return __('seo.rank_check_failed');
    }

    /**
     * 立即刷新排名（任务 #36-7：不再要求 SerpApi——内置 Bing/百度抓取兜底恒可用；
     * Google 引擎在服务器不可达时报 rank_check_failed，可稍后重试或手动录入）
     *
     * @return RedirectResponse
     */
    public function refresh(Request $request, SeoKeyword $keyword, RankTracker $tracker)
    {
        $this->authorizeOwn($request, $keyword);

        try {
            $rank = $tracker->check($keyword);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['keyword' => $this->rankFailureMessage($e)]);
        }

        $position = $rank->position;

        return back()->with('success', __('seo.rank_checked', ['position' => is_scalar($position) ? (string) $position : __('seo.not_ranked')]));
    }

    /**
     * 更新跟踪设置（开关 / 间隔）
     *
     * @return RedirectResponse
     */
    public function update(Request $request, SeoKeyword $keyword)
    {
        $this->authorizeOwn($request, $keyword);

        $validated = Typed::arr($request->validate([
            'is_enabled' => 'nullable|boolean',
            'check_interval' => 'nullable|in:never,daily,weekly,monthly',
        ]));

        $keyword->update([
            'is_enabled' => (bool) ($validated['is_enabled'] ?? $keyword->is_enabled),
            'check_interval' => $validated['check_interval'] ?? $keyword->check_interval,
        ]);

        return back()->with('success', __('seo.keyword_updated'));
    }

    /**
     * @return RedirectResponse
     */
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

        $owned = Website::where('user_id', $this->user()->user_id)->where('website_id', $websiteId)->value('website_id');

        return ((bool) $owned) ? Typed::int($owned) : null;
    }

    protected function authorizeOwn(Request $request, SeoKeyword $keyword): void
    {
        if ($keyword->user_id !== $this->user()->user_id && ! $this->user()->isAdmin()) {
            abort(403);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * Monit 网站详情统计页面
 * 依据规格书 §6.2.2：单个网站的多维度分析
 */
class StatsController extends Controller
{
    /**
     * AI 数据洞察（规格书 §12.6：统计摘要喂给国内大模型生成分析）
     * POST /stats/{website}/ai-insight，throttle + can:own + ai 设置组双重门控
     */
    public function aiInsight(Request $request, Website $website)
    {
        $ai = app(\App\Services\Ai\AiService::class);

        if (! $ai->insightsEnabled()) {
            return response()->json(['error' => 'ai_disabled'], 403);
        }

        $range = (int) ($request->input('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $overview = $stats->overview();

        $top = fn (string $dimension, int $limit = 5) => collect($stats->breakdown($dimension, $limit))
            ->map(fn ($row) => ($row['label'] ?? $row['name'] ?? '?').' ('.($row['value'] ?? $row['count'] ?? 0).')')
            ->implode('、');

        $prompt = sprintf(
            "网站「%s」（%s）最近 %d 天统计：浏览量 %d、访客 %d、会话 %d、跳出率 %s%%、平均停留 %s 秒。"."\n".
            "热门页面：%s。"."\n".
            "主要来源：%s。"."\n".
            "访客地区：%s。",
            $website->name,
            $website->host ?? $website->domain,
            $range,
            (int) ($overview['pageviews'] ?? 0),
            (int) ($overview['visitors'] ?? 0),
            (int) ($overview['sessions'] ?? 0),
            (string) ($overview['bounce_rate'] ?? 0),
            (string) ($overview['avg_duration'] ?? 0),
            $top('path') ?: '无数据',
            $top('referrer_host') ?: '直接访问为主',
            $top('country_code') ?: '无数据',
        );

        $result = $ai->chat(
            $prompt,
            '你是一名网站数据分析顾问。基于给定统计数据输出简明中文洞察：流量趋势判断、来源/地域结构亮点、2-3 条可执行的优化建议。不超过 300 字，不要使用 Markdown 标题。',
            ['max_tokens' => 800],
        );

        if (! $result['ok']) {
            return response()->json(['error' => $result['error'] ?? 'ai_failed'], 502);
        }

        return response()->json([
            'insight' => $result['content'],
            'provider' => $result['provider'],
            'model' => $result['model'],
        ]);
    }

    /**
     * 网站统计概览
     */
    public function index(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        // 规格 §5.3 AnalyticsFilters：path/utm/来源/地域/设备等过滤
        $filters = $request->only(StatisticsService::FILTER_DIMENSIONS);

        $stats = StatisticsService::for($website)->lastDays($range)->filters($filters);

        return view('stats.index', [
            'website' => $website,
            'range' => $range,
            'overview' => $stats->overview(),
            'realtime' => $stats->realtime(),
            'series' => $stats->dailySeries(),
            'topPaths' => $stats->breakdown('path'),
            'topReferrers' => $stats->breakdown('referrer_host'),
            'topCountries' => $stats->breakdown('country_code', 8),
            'topDevices' => $stats->breakdown('device_type', 4),
            'topBrowsers' => $stats->breakdown('browser_name', 4),
            'topOs' => $stats->breakdown('os_name', 4),
        ]);
    }

    /**
     * 实时在线（规格书 §6.2.1：/realtime，按秒刷新）
     */
    public function realtime(Request $request, Website $website)
    {
        $stats = StatisticsService::for($website);

        return view('stats.realtime', [
            'website' => $website,
            'count' => $stats->realtime(),
            'overview' => $stats->lastDays(1)->overview(),
        ]);
    }

    /**
     * 实时数据 JSON 端点（页面轮询用）
     */
    public function realtimeData(Request $request, Website $website)
    {
        return response()->json([
            'count' => StatisticsService::for($website)->realtime(),
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * 访客列表
     */
    public function visitors(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        // M22 访客导出（原版 Visitors.php process_export_json，规格书 §6.2.2）
        if (in_array($request->query('export'), ['json', 'csv'], true)) {
            return $this->exportVisitors($website, $stats, $request->query('export'));
        }

        return view('stats.visitors', [
            'website' => $website,
            'range' => $range,
            'visitors' => $stats->topVisitors(50),
        ]);
    }

    /**
     * M22 访客列表导出：JSON / CSV（最近 90 天，LIMIT 5000 对齐原版）
     * 需套餐 export 功能（规格书 §10.2 export；-1/1 视为启用，0 视为关闭）
     */
    protected function exportVisitors(Website $website, StatisticsService $stats, string $format)
    {
        $user = request()->user();

        if (! $user || (int) ($user->getPlanSettings()['export'] ?? 1) === 0) {
            abort(403, __('stats.export_not_allowed'));
        }

        $visitors = \App\Models\WebsiteVisitor::query()
            ->where('website_id', $website->website_id)
            ->where('last_date', '>=', now()->subDays(90))
            ->orderByDesc('last_date')
            ->limit(5000)
            ->get([
                'visitor_id', 'website_id', 'ip', 'custom_parameters',
                'continent_code', 'country_code', 'city_name',
                'os_name', 'os_version', 'browser_name', 'browser_version',
                'browser_language', 'browser_timezone', 'screen_resolution', 'device_type',
                'total_sessions', 'total_goals_conversions', 'date', 'last_date',
            ]);

        $filename = 'visitors_'.$website->website_id.'_'.now()->format('Ymd_His');

        if ($format === 'csv') {
            $callback = function () use ($visitors) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_keys($visitors->first()?->getAttributes() ?? ['visitor_id' => '']));

                foreach ($visitors as $v) {
                    fputcsv($out, array_map(
                        fn ($val) => is_string($val) ? mb_substr($val, 0, 2000) : $val,
                        $v->getAttributes()
                    ));
                }

                fclose($out);
            };

            return response()->streamDownload($callback, $filename.'.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return response()->json([
            'website_id' => $website->website_id,
            'generated_at' => now()->toISOString(),
            'count' => $visitors->count(),
            'visitors' => $visitors,
        ], 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'.json"',
        ]);
    }

    /**
     * 来源分析
     */
    public function referrers(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.referrers', [
            'website' => $website,
            'range' => $range,
            'topReferrers' => $stats->breakdownWithUtm('referrer_host', 50),
        ]);
    }

    /**
     * 目标转化管理
     */
    public function goals(Request $request, Website $website)
    {
        return view('stats.goals', [
            'website' => $website,
            'goals' => $website->goals()->orderBy('goal_id')->get(),
        ]);
    }

    /**
     * 出站点击统计
     */
    public function outboundClicks(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $rangeDate = now()->subDays($range);

        $clicks = $website->outboundClicks()
            ->where('datetime', '>=', $rangeDate)
            ->orderBy('datetime', 'desc')
            ->paginate(50);

        return view('stats.outbound_clicks', [
            'website' => $website,
            'range' => $range,
            'clicks' => $clicks,
        ]);
    }

        /**
     * 统计概览（别名路由，复用 index 逻辑）
     */
    public function overview(Request $request, Website $website)
    {
        return $this->index($request, $website);
    }

    /**
     * 事件列表
     */
    public function events(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $overview = $stats->overview();
        $series = $stats->dailySeries();

        // 事件类型分组统计
        $eventsByType = $stats->breakdown('type', 20);

        return view('stats.events', [
            'website' => $website,
            'range' => $range,
            'overview' => $overview,
            'series' => $series,
            'eventsByType' => $eventsByType,
        ]);
    }

    /**
     * 热门页面
     */
    public function topPages(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $topPaths = $stats->breakdown('path', 50);

        return view('stats.top_pages', [
            'website' => $website,
            'range' => $range,
            'topPaths' => $topPaths,
        ]);
    }

    /**
     * 热门来源（别名，复用 referrers）
     */
    public function topReferrers(Request $request, Website $website)
    {
        return $this->referrers($request, $website);
    }

    /**
     * 热门国家
     */
    public function topCountries(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $topCountries = $stats->breakdown('country_code', 50);

        return view('stats.top_countries', [
            'website' => $website,
            'range' => $range,
            'topCountries' => $topCountries,
        ]);
    }

    /**
     * 热门浏览器
     */
    public function topBrowsers(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $topBrowsers = $stats->breakdown('browser_name', 50);

        return view('stats.top_browsers', [
            'website' => $website,
            'range' => $range,
            'topBrowsers' => $topBrowsers,
        ]);
    }

    /**
     * 热门设备
     */
    public function topDevices(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $topDevices = $stats->breakdown('device_type', 50);

        return view('stats.top_devices', [
            'website' => $website,
            'range' => $range,
            'topDevices' => $topDevices,
        ]);
    }

    /**
     * 热门操作系统
     */
    public function topOperatingSystems(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);
        $topOs = $stats->breakdown('os_name', 50);

                return view('stats.top_operating_systems', [
            'website' => $website,
            'range' => $range,
            'topOs' => $topOs,
        ]);
    }

    /**
     * M21 行为分析（GA/CNZZ 对标）：时段 + 渠道 + 入口页 + 离开页 + 搜索词 + 忠诚度
     */
    public function behavior(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.behavior', [
            'website' => $website,
            'range' => $range,
            'hourly' => $stats->hourlySeries(),
            'weekdays' => $stats->weekdaySeries(),
            'channels' => $stats->channels(),
            'landingPages' => $stats->landingPages(10),
            'exitPages' => $stats->exitPages(10),
            'searchTerms' => $stats->searchTerms(10),
            'loyalty' => $stats->loyalty(),
        ]);
    }

    /**
     * M21 热门城市（GA「位置」/CNZZ「地域分布」）
     */
    public function topCities(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_cities', [
            'website' => $website,
            'range' => $range,
            'topCities' => $stats->breakdown('city_name', 50),
        ]);
    }

    /**
     * M21 热门语言（GA「用户语言」）
     */
    public function topLanguages(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_languages', [
            'website' => $website,
            'range' => $range,
            'topLanguages' => $stats->breakdown('browser_language', 50),
        ]);
    }

    /**
     * M21 热门分辨率（CNZZ「分辨率」）
     */
    public function topResolutions(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_resolutions', [
            'website' => $website,
            'range' => $range,
            'topResolutions' => $stats->breakdown('screen_resolution', 50),
        ]);
    }

    /**
     * M22 热门浏览器时区（原版 browser-timezones 页，规格书 §5.1.1）
     */
    public function topTimezones(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_timezones', [
            'website' => $website,
            'range' => $range,
            'topTimezones' => $stats->breakdown('browser_timezone', 50),
        ]);
    }

    /**
     * M22 大洲分布（原版 continents 页，规格书 §5.1.1）
     */
    public function topContinents(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_continents', [
            'website' => $website,
            'range' => $range,
            'topContinents' => $stats->breakdown('continent_code', 10),
        ]);
    }

    /**
     * M22 明暗主题偏好（原版 themes 页，规格书 §5.1.1）
     */
    public function topThemes(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.top_themes', [
            'website' => $website,
            'range' => $range,
            'topThemes' => $stats->breakdown('theme', 10),
        ]);
    }

    /**
     * M22 引荐分类页（原版 social/search/ai_referrers 三页合一，规格书 §5.5）
     */
    public function referralCategories(Request $request, Website $website)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.referral_categories', array_merge([
            'website' => $website,
            'range' => $range,
        ], $stats->referralCategories()));
    }

    /**
     * M22 引荐路径钻取（原版 referrer_paths_modal，规格书 §5.5）
     */
    public function referrerPaths(Request $request, Website $website, string $host)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.referrer_paths', [
            'website' => $website,
            'range' => $range,
            'host' => $host,
            'paths' => $stats->referrerPaths($host, 100),
        ]);
    }

    /**
     * M22 UTM 钻取（原版 utms_medium_campaign_modal，规格书 §5.5）
     */
    public function utmDrilldown(Request $request, Website $website, string $source)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.utm_drilldown', [
            'website' => $website,
            'range' => $range,
            'source' => $source,
            'items' => $stats->utmDrilldown($source, 100),
        ]);
    }

    /**
     * M22 出站路径钻取（原版 outbound_clicks_paths_modal，规格书 §5.5）
     */
    public function outboundClickPaths(Request $request, Website $website, string $host)
    {
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('stats.outbound_click_paths', [
            'website' => $website,
            'range' => $range,
            'host' => $host,
            'paths' => $stats->outboundClickPaths($host, 100),
        ]);
    }

    /**
     * 单访客详情（规格书 §6.2.2：/visitor）
     */
    public function visitorDetail(Request $request, Website $website, int $visitorId)
    {
        $visitor = \App\Models\WebsiteVisitor::where('website_id', $website->website_id)
            ->findOrFail($visitorId);

        $sessions = \App\Models\VisitorSession::where('visitor_id', $visitorId)
            ->with('events')
            ->orderByDesc('date')
            ->get();

        return view('stats.visitor', compact('website', 'visitor', 'sessions'));
    }

    /**
     * 单会话详情（规格书 §6.2.2：/session-ajax）
     */
    public function sessionDetail(Request $request, Website $website, int $sessionId)
    {
        $session = \App\Models\VisitorSession::where('website_id', $website->website_id)
            ->with(['visitor', 'events'])
            ->findOrFail($sessionId);

        return view('stats.session', compact('website', 'session'));
    }
}
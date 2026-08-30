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

        return view('stats.visitors', [
            'website' => $website,
            'range' => $range,
            'visitors' => $stats->topVisitors(50),
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
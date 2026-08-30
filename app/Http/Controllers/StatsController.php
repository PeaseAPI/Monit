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
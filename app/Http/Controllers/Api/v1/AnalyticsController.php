<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * API v1 - 数据分析接口
 * 规格书 §4.2：Analytics API
 */
class AnalyticsController
{
    protected function resolveStats(Website $website, int $range): StatisticsService
    {
        return StatisticsService::for($website)->lastDays($range);
    }

    protected function validateRange(int $range): int
    {
        return in_array($range, [1, 7, 30, 90], true) ? $range : 7;
    }

    public function realtime(Website $website)
    {
        $stats = StatisticsService::for($website);

        return response()->json(['realtime' => $stats->realtime()]);
    }

    public function visitors(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json(['visitors' => $stats->topVisitors(50)]);
    }

    public function events(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json([
            'overview' => $stats->overview(),
            'events_by_type' => $stats->breakdown('type', 20),
        ]);
    }

    public function metrics(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json([
            'overview' => $stats->overview(),
            'series' => $stats->dailySeries(),
        ]);
    }

    public function topPages(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_pages' => $stats->breakdown('path', $limit)]);
    }

    public function topReferrers(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_referrers' => $stats->breakdownWithUtm('referrer_host', $limit)]);
    }

    public function topCountries(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_countries' => $stats->breakdown('country_code', $limit)]);
    }

    public function topBrowsers(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_browsers' => $stats->breakdown('browser_name', $limit)]);
    }

    public function topDevices(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_devices' => $stats->breakdown('device_type', $limit)]);
    }

    public function topOperatingSystems(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = (int) $request->query('limit', 50);
        $stats = $this->resolveStats($website, $range);

        return response()->json(['top_os' => $stats->breakdown('os_name', $limit)]);
    }

    public function sessions(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json([
            'overview' => $stats->overview(),
            'series' => $stats->dailySeries(),
        ]);
    }

    public function goals(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json(['goals' => $stats->goalsConversions()]);
    }

    public function utm(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $stats = $this->resolveStats($website, $range);

        return response()->json(['utm' => $stats->utmAnalysis()]);
    }

    /**
     * 统计聚合查询（规格书 §8：/api/statistics 最大端点）
     * 一次返回 overview + series + 全部 top 维度。
     */
    public function statistics(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = min(100, max(1, (int) $request->query('limit', 50)));
        $stats = $this->resolveStats($website, $range);

        return response()->json([
            'website' => [
                'website_id' => $website->website_id,
                'host' => $website->host,
                'tracking_type' => $website->tracking_type,
            ],
            'range' => $range,
            'overview' => $stats->overview(),
            'series' => $stats->dailySeries(),
            'top_pages' => $stats->breakdown('path', $limit),
            'top_referrers' => $stats->breakdown('referrer_host', $limit),
            'top_countries' => $stats->breakdown('country_code', $limit),
            'top_browsers' => $stats->breakdown('browser_name', $limit),
            'top_operating_systems' => $stats->breakdown('os_name', $limit),
            'top_devices' => $stats->breakdown('device_type', $limit),
            'goals' => $stats->goalsConversions(),
            'utm' => $stats->utmAnalysis(),
        ]);
    }

    /**
     * 高级模式页面浏览（规格书 §8：/api/pageviews-advanced）
     * 数据源 sessions_events（landing_page / pageview）。
     */
    public function pageviewsAdvanced(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        [$start, $end] = $this->resolveRangeDates($range);

        $rows = \DB::table('sessions_events')
            ->where('website_id', $website->website_id)
            ->whereBetween('date', [$start, $end])
            ->whereIn('type', ['landing_page', 'pageview'])
            ->groupBy('path')
            ->selectRaw('path, count(*) as pageviews, count(distinct visitor_id) as visitors')
            ->orderByDesc('pageviews')
            ->limit($limit)
            ->get();

        return response()->json([
            'mode' => 'advanced',
            'range' => $range,
            'pageviews' => $rows->map(fn ($row) => [
                'path' => (string) $row->path,
                'pageviews' => (int) $row->pageviews,
                'visitors' => (int) $row->visitors,
            ])->all(),
        ]);
    }

    /**
     * 轻量模式页面浏览（规格书 §8：/api/pageviews-lightweight）
     * 数据源 lightweight_events（landing_page / pageview；轻量模式无访客维度，仅计 pageviews）。
     */
    public function pageviewsLightweight(Website $website, Request $request)
    {
        $range = $this->validateRange((int) $request->query('range', 7));
        $limit = min(200, max(1, (int) $request->query('limit', 50)));
        [$start, $end] = $this->resolveRangeDates($range);

        $rows = \DB::table('lightweight_events')
            ->where('website_id', $website->website_id)
            ->whereBetween('date', [$start, $end])
            ->whereIn('type', ['landing_page', 'pageview'])
            ->groupBy('path')
            ->selectRaw('path, count(*) as pageviews')
            ->orderByDesc('pageviews')
            ->limit($limit)
            ->get();

        return response()->json([
            'mode' => 'lightweight',
            'range' => $range,
            'pageviews' => $rows->map(fn ($row) => [
                'path' => (string) $row->path,
                'pageviews' => (int) $row->pageviews,
            ])->all(),
        ]);
    }

    /**
     * 会话回放（规格书 §8：/api/replays）
     */
    public function replays(Website $website, Request $request)
    {
        $limit = min(100, max(1, (int) $request->query('limit', 25)));

        $replays = \App\Models\SessionReplay::with(['session', 'visitor'])
            ->where('website_id', $website->website_id)
            ->orderByDesc('replay_id')
            ->limit($limit)
            ->get();

        return response()->json([
            'replays' => $replays->map(fn ($replay) => [
                'replay_id' => $replay->replay_id,
                'session_id' => $replay->session_id,
                'visitor_id' => $replay->visitor_id,
                'is_offloaded' => $replay->is_offloaded,
                'datetime' => optional($replay->datetime)->toIso8601String(),
                'country_code' => $replay->visitor?->country_code,
                'device_type' => $replay->visitor?->device_type,
                'os_name' => $replay->visitor?->os_name,
                'browser_name' => $replay->visitor?->browser_name,
            ])->all(),
        ]);
    }

    /** 把 range 天数换算为 [start, end] 日期（与 StatisticsService::lastDays 对齐） */
    protected function resolveRangeDates(int $range): array
    {
        return [
            now()->subDays($range)->toDateString(),
            now()->toDateString(),
        ];
    }

}
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
}
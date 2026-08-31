<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * Monit 仪表盘
 * 依据规格书 §6.2.1：/dashboard?website_id=&range=1|7|30|custom
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $websites = $user->websites()
            ->where('is_enabled', true)
            ->orderBy('website_id')
            ->get();

        if ($websites->isEmpty()) {
            return view('dashboard.empty', ['websites' => $websites]);
        }

        $website = $websites->firstWhere('website_id', (int) $request->query('website_id')) ?? $websites->first();

        // 时间范围：1/7/30 天
        $range = (int) ($request->query('range') ?: 7);
        if (! in_array($range, [1, 7, 30], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('dashboard.index', [
            'websites' => $websites,
            'website' => $website,
            'range' => $range,
            'overview' => $stats->overview(),
            'realtime' => $stats->realtime(),
            'series' => $stats->dailySeries(),
            'topPaths' => $stats->breakdown('path'),
            'topReferrers' => $stats->breakdown('referrer_host'),
            'topCountries' => $stats->breakdown('country_code', 8),
            'topDevices' => $stats->breakdown('device_type', 4),
        ]);
    }

    /**
     * 像素安装指引
     */
    public function install(Request $request, Website $website)
    {
        return view('dashboard.install', ['website' => $website]);
    }
}

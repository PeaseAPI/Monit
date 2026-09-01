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

        // 保存的仪表盘视图（§6.2.1：DashboardViews，用户可切换/管理）
        $savedViews = \App\Models\DashboardView::where('user_id', $user->user_id)
            ->orderBy('order')
            ->orderBy('dashboard_view_id')
            ->get();

        return view('dashboard.index', [
            'websites' => $websites,
            'website' => $website,
            'range' => $range,
            'savedViews' => $savedViews,
            'overview' => $stats->overview(),
            'realtime' => $stats->realtime(),
            'series' => $stats->dailySeries(),
            'topPaths' => $stats->breakdown('path'),
            'topReferrers' => $stats->breakdown('referrer_host'),
            'topCountries' => $stats->breakdown('country_code', 8),
            'topDevices' => $stats->breakdown('device_type', 4),
            'topOses' => $stats->breakdown('os_name', 6),
            'topBrowsers' => $stats->breakdown('browser_name', 6),
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

<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * 用户中心 - 轻量页面浏览分析
 * 规格书 §6.2.2：/pageviews-lightweight
 */
class PageviewsLightweightController extends Controller
{
    public function index(Request $request, Website $website)
    {
        $range = (int) ($request->query('range', 7));
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $stats = StatisticsService::for($website)->lastDays($range);

        return view('pageviews.lightweight', [
            'website' => $website,
            'range' => $range,
            'overview' => $stats->overview(),
            'series' => $stats->dailySeries(),
            'topPaths' => $stats->breakdown('path', 25),
        ]);
    }
}

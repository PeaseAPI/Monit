<?php

namespace App\Http\Controllers;

use App\Models\OutboundClick;
use App\Models\Website;
use Illuminate\Http\Request;

/**
 * 用户中心 - 出站点击统计
 * 规格书 §6.2.2：/outbound-clicks
 */
class OutboundClicksController extends Controller
{
        public function index(Request $request, Website $website)
    {
        $range = (int) ($request->query('range', 7));
        if (! in_array($range, [1, 7, 30, 90], true)) {
            $range = 7;
        }

        $rangeDate = now()->subDays($range);

        $clicks = OutboundClick::where('website_id', $website->website_id)
            ->where('datetime', '>=', $rangeDate)
            ->selectRaw('host, url, COUNT(*) as count, MAX(datetime) as datetime')
            ->groupBy('host', 'url')
            ->orderByDesc('count')
            ->paginate(50);

        return view('outbound-clicks.index', compact('website', 'range', 'clicks'));
    }
}

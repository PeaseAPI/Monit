<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\WebsiteVisitor;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * 用户中心 - 访客详情
 * 规格书 §6.2.2：/visitor（单访客详情页）
 */
class VisitorController extends Controller
{
    public function show(Request $request, Website $website, int $visitorId)
    {
        $visitor = WebsiteVisitor::where('website_id', $website->website_id)
            ->findOrFail($visitorId);

        $sessions = $visitor->sessions()
            ->orderByDesc('date')
            ->limit(50)
            ->get();

        return view('visitors.show', compact('website', 'visitor', 'sessions'));
    }
}

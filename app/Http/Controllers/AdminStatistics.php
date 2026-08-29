<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Website;
use App\Models\Payment;
use App\Models\WebsiteGoal;
use App\Models\WebsiteVisitor;
use Illuminate\Http\Request;

/**
 * 管理后台 - 统计概览
 * 规格书 §6.3：AdminStatistics
 */
class AdminStatistics extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 1)->count();
                $newUsersToday = User::whereDate('created_at', today())->count();
        $totalWebsites = Website::count();
        $enabledWebsites = Website::where('is_enabled', true)->count();
        $totalPayments = Payment::where('status', 1)->count();
        $totalRevenue = Payment::where('status', 1)->sum('total_amount') ?? 0;
        $monthlyRevenue = Payment::where('status', 1)
            ->whereMonth('datetime', now()->month)
            ->whereYear('datetime', now()->year)
            ->sum('total_amount') ?? 0;
        $totalVisitors = WebsiteVisitor::count();
        $totalSessions = \App\Models\VisitorSession::count();
        $totalEvents = \App\Models\SessionEvent::count();

        // 按套餐分布
        $planDistribution = User::groupBy('plan_id')->selectRaw('plan_id, count(*) as count')->get();

        // 按日活跃用户
        $dailyActiveUsers = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = User::whereDate('last_activity', '>=', $date)->whereDate('last_activity', '<', now()->subDays($i - 1)->format('Y-m-d'))->count();
            $dailyActiveUsers[] = ['date' => $date, 'count' => $count];
        }

                return view('admin.statistics.index', compact(
            'totalUsers', 'activeUsers', 'newUsersToday',
            'totalWebsites', 'enabledWebsites',
            'totalPayments', 'totalRevenue', 'monthlyRevenue',
            'totalVisitors', 'totalSessions', 'totalEvents',
            'planDistribution', 'dailyActiveUsers'
        ))->with('adminNav', 'statistics');
    }
}

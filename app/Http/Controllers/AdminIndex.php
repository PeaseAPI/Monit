<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Website;

/**
 * 管理后台 - 概览
 * 规格书 §6.3.1：AdminIndex
 */
class AdminIndex extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 1)->count();
        $totalWebsites = Website::count();
        $enabledWebsites = Website::where('is_enabled', true)->count();
        $totalPayments = Payment::where('status', 1)->count();
        $monthlyRevenue = Payment::where('status', 1)
            ->whereMonth('datetime', now()->month)
            ->whereYear('datetime', now()->year)
            ->sum('total_amount') ?? 0;

        // 最近注册用户
        $recentUsers = User::orderByDesc('created_at')->limit(20)->get();

        // 最近支付
        $recentPayments = Payment::with('user')->orderByDesc('datetime')->limit(20)->get();

        return view('admin.index', compact(
            'totalUsers', 'activeUsers', 'totalWebsites',
            'enabledWebsites', 'totalPayments', 'monthlyRevenue',
            'recentUsers', 'recentPayments'
        ))->with('adminNav', 'dashboard');
    }
}

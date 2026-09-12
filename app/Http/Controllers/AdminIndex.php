<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Heatmap;
use App\Models\Payment;
use App\Models\SessionReplay;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteGoal;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * 管理后台 - 概览（对标原版 admin 仪表台：8 张统计卡 + 最新用户/支付）
 * 规格书 §6.3.1：AdminIndex
 */
class AdminIndex extends Controller
{
    /**
     * @return View
     */
    public function index()
    {
        // 原版仪表台统计卡（总数 + 本月增量）
        $stats = [
            'websites' => [
                'total' => Website::count(),
                'month' => Website::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'route' => route('admin.websites.index'),
            ],
            'replays' => [
                'total' => SessionReplay::count(),
                'month' => SessionReplay::whereMonth('datetime', now()->month)->whereYear('datetime', now()->year)->count(),
                'route' => route('admin.replays.index'),
            ],
            'heatmaps' => [
                'total' => Heatmap::count(),
                'month' => Heatmap::whereMonth('datetime', now()->month)->whereYear('datetime', now()->year)->count(),
                'route' => route('admin.heatmaps.index'),
            ],
            'goals' => [
                'total' => WebsiteGoal::count(),
                'month' => WebsiteGoal::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'route' => route('admin.websites.index'),
            ],
            'domains' => [
                'total' => Domain::count(),
                'month' => Domain::whereMonth('datetime', now()->month)->whereYear('datetime', now()->year)->count(),
                'route' => route('admin.domains.index'),
            ],
            'users' => [
                'total' => User::count(),
                'month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'route' => route('admin.users.index'),
            ],
            'payments' => [
                'total' => Payment::where('status', 1)->count(),
                'month' => Payment::where('status', 1)->whereMonth('datetime', now()->month)->whereYear('datetime', now()->year)->count(),
                'route' => route('admin.payments.index'),
            ],
        ];

        $activeUsers = User::where('status', 1)->count();
        $monthlyRevenue = (float) Payment::where('status', 1)
            ->whereMonth('datetime', now()->month)
            ->whereYear('datetime', now()->year)
            ->sum('total_amount');

        // 最新用户（对标原版 latest users 表：头像/状态/套餐/注册时间/操作）
        $recentUsers = User::orderByDesc('created_at')->limit(10)->get();

        // 最近支付
        $recentPayments = Payment::with('user')->orderByDesc('datetime')->limit(10)->get();

        // Cron 健康状态（cron.last_run_at 由 CronController 每次运行写入）
        // state: ok（3 分钟内有运行）/ stale（≥3 分钟未运行，调度疑似停摆）/ never（从未运行）
        $lastRunAt = Settings::get('cron.last_run_at');
        $cronHealth = ['state' => 'never', 'minutes' => null, 'last_run_at' => null];

        if (is_string($lastRunAt) && $lastRunAt !== '') {
            try {
                $last = Carbon::parse($lastRunAt);
                $minutes = (int) abs($last->diffInMinutes(now()));
                $cronHealth = [
                    'state' => $minutes < 3 ? 'ok' : 'stale',
                    'minutes' => $minutes,
                    'last_run_at' => $last->format('Y-m-d H:i'),
                ];
            } catch (\Throwable) {
                // 不可解析的时间串按从未运行处理
            }
        }

        return view('admin.index', compact(
            'stats', 'activeUsers', 'monthlyRevenue', 'recentUsers', 'recentPayments', 'cronHealth'
        ))->with('adminNav', 'dashboard');
    }
}

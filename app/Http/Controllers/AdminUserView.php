<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 查看用户详情（规格书 §6.3.2：AdminUserView）
 */
class AdminUserView extends Controller
{
    public function index(Request $request, int $userId): View
    {
        $user = User::findOrFail($userId);
        $websites = \App\Models\Website::where('user_id', $userId)->orderByDesc('datetime')->get();
        $payments = \App\Models\Payment::where('user_id', $userId)->orderByDesc('datetime')->limit(50)->get();
        $logs = \App\Models\AccountLog::where('user_id', $userId)->orderByDesc('datetime')->limit(50)->get();

        $websiteIds = $websites->pluck('website_id');
        $sessions = \App\Models\VisitorSession::whereIn('website_id', $websiteIds)->count();
        $totalPageviews = \App\Models\SessionEvent::whereIn('website_id', $websiteIds)
            ->whereIn('type', ['landing_page', 'pageview'])->count();

        return view('admin.users.view', compact('user', 'websites', 'payments', 'logs', 'sessions', 'totalPageviews'))
            ->with('adminNav', 'users');
    }
}

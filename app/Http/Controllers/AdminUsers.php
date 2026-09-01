<?php

namespace App\Http\Controllers;

use App\Models\AccountLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 管理后台 - 用户管理
 * 规格书 §6.3.2：AdminUsers / AdminUserCreate / AdminUserUpdate / AdminUserView / AdminUsersLogs
 */
class AdminUsers extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        if ($plan = $request->query('plan')) {
            $query->where('plan_id', $plan);
        }

        if ($request->query('status') !== null && $request->query('status') !== '') {
            $query->where('status', (int) $request->query('status'));
        }

        // 特权筛选（对标原版：管理员/普通用户）
        if ($request->query('type') !== null && $request->query('type') !== '') {
            $query->where('type', (int) $request->query('type'));
        }

        $users = $query->orderByDesc('created_at')->paginate(50);
        $plans = Plan::orderBy('order')->get();

        return view('admin.users.index', compact('users', 'plans'))->with('adminNav', 'users');
    }
}

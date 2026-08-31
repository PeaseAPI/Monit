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

        $users = $query->orderByDesc('created_at')->paginate(50);
        $plans = Plan::orderBy('order')->get();

        return view('admin.users.index', compact('users', 'plans'))->with('adminNav', 'users');
    }

    public function create()
    {
        $plans = Plan::orderBy('order')->get();

        return view('admin.users.create', compact('plans'))->with('adminNav', 'users');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'plan_id' => ['required', 'string'],
            'type' => ['required', 'in:0,1'],
        ]);

        $user = User::create([
            ...$validated,
            'api_key' => Str::random(60),
            'referral_key' => Str::random(32),
            'language' => 'zh_CN',
            'timezone' => 'Asia/Shanghai',
            'status' => 1,
            'ip' => $request->ip(),
            'source' => 'admin',
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', __('msg.user_created', ['name' => $validated['name']]));
    }

    public function edit(int $userId)
    {
        $user = User::findOrFail($userId);
        $plans = Plan::orderBy('order')->get();

        return view('admin.users.edit', compact('user', 'plans'))->with('adminNav', 'users');
    }

    public function update(Request $request, int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email,'.$userId.',user_id'],
            'type' => ['required', 'in:0,1'],
            'plan_id' => ['required', 'string'],
            'plan_expiration_date' => ['nullable', 'date'],
            'status' => ['required', 'in:0,1'],
        ]);

        $user->update($validated);

        if ($request->filled('password')) {
            $validated['password'] = $request->validate(['password' => ['min:8']]);
            $user->update(['password' => Hash::make($validated['password']['password'])]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', __('msg.user_updated', ['name' => $user->name]));
    }

    public function view(int $userId)
    {
        $user = User::with('websites')->findOrFail($userId);
        $logs = AccountLog::where('user_id', $userId)->orderByDesc('datetime')->limit(100)->get();

        return view('admin.users.view', compact('user', 'logs'))->with('adminNav', 'users');
    }

    public function toggleStatus(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        $user->update(['status' => $user->status === 1 ? 0 : 1]);

        $status = $user->status === 1 ? __('msg.status_activated') : __('msg.status_disabled');

        return back()->with('success', __('msg.user_status_toggled', ['name' => $user->name, 'status' => $status]));
    }

    public function logs(Request $request)
    {
        $query = AccountLog::with('user');

        if ($search = $request->query('search')) {
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"));
        }

        $logs = $query->orderByDesc('datetime')->paginate(100);

        return view('admin.users.logs', compact('logs'))->with('adminNav', 'users');
    }
}

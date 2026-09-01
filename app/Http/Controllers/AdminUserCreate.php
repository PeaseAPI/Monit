<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * 管理后台 - 创建用户（规格书 §6.3.2：AdminUserCreate）
 */
class AdminUserCreate extends Controller
{
    public function index(): View
    {
        $plans = Plan::where('is_enabled', true)->orderBy('order')->get();

        return view('admin.users.create', compact('plans'))->with('adminNav', 'users');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['nullable', 'integer', 'in:0,1'],
            'plan_id' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            // 本土默认：时区 Asia/Shanghai、语言 zh_CN（可显式覆盖）
            'language' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['api_key'] = Str::random(60);
        $validated['referral_key'] = Str::random(32);
        $validated['plan_id'] = $validated['plan_id'] ?? 'free';
        $validated['status'] = (int) ($validated['status'] ?? 1);
        $validated['type'] = (int) ($validated['type'] ?? 0);
        $validated['language'] = $validated['language'] ?? 'zh_CN';
        $validated['timezone'] = $validated['timezone'] ?? 'Asia/Shanghai';
        $validated['ip'] = $request->ip();
        $validated['source'] = 'admin';

        $user = User::create($validated);

        return redirect()->route('admin.users.edit', $user->user_id)
            ->with('success', __('msg.user_created'));
    }
}

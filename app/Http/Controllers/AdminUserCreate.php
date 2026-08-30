<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 创建用户（规格书 §6.3.2：AdminUserCreate）
 */
class AdminUserCreate extends Controller
{
    public function index(): View
    {
        $plans = \App\Models\Plan::where('is_enabled', true)->orderBy('order')->get();
        return view('admin.users.create', compact('plans'))->with('adminNav', 'users');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['nullable', 'in:0,1'],
            'plan_id' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'in:0,1'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['api_key'] = \Illuminate\Support\Str::random(60);
        $validated['referral_key'] = \Illuminate\Support\Str::random(32);
        $validated['plan_id'] = $validated['plan_id'] ?? 'free';
        $validated['status'] = $validated['status'] ?? 1;
        $validated['type'] = $validated['type'] ?? 0;
        $validated['ip'] = $request->ip();

        $user = User::create($validated);

        return redirect()->route('admin.users.edit', $user->user_id)
            ->with('success', __('msg.user_created'));
    }
}

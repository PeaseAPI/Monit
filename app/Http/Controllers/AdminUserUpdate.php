<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 编辑用户（规格书 §6.3.2：AdminUserUpdate）
 */
class AdminUserUpdate extends Controller
{
    public function index(Request $request, int $userId): View
    {
        $user = User::findOrFail($userId);
        $plans = \App\Models\Plan::where('is_enabled', true)->orderBy('order')->get();
        $payments = \App\Models\Payment::where('user_id', $userId)->orderByDesc('datetime')->limit(20)->get();
        $websites = \App\Models\Website::where('user_id', $userId)->orderByDesc('datetime')->limit(20)->get();

        return view('admin.users.edit', compact('user', 'plans', 'payments', 'websites'))
            ->with('adminNav', 'users');
    }

    public function update(Request $request, int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email,' . $userId . ',user_id'],
            'password' => ['nullable', 'string', 'min:8'],
            'type' => ['nullable', 'in:0,1'],
            'plan_id' => ['nullable', 'string', 'max:64'],
            'plan_expiration_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:0,1'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return back()->with('success', __('msg.user_updated'));
    }

    public function toggleStatus(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        $user->update(['status' => $user->status ? 0 : 1]);
        return back()->with('success', __('msg.user_status_toggled'));
    }

    public function loginAs(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        auth()->login($user, true);
        return redirect()->route('dashboard');
    }
}

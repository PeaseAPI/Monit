<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理后台 - 编辑用户（规格书 §6.3.2：AdminUserUpdate）
 *
 * 对标 monit.cn /admin/user-update 全量字段：
 * 基本（name/email/status 三态/type 特权/referred_by）
 * + 套餐（plan_id/plan_trial_done/plan_expiration_date）
 * + 自定义限额与权限（users.plan_settings JSON，20 键）
 * + 安全（新密码 + 重复确认）
 */
class AdminUserUpdate extends Controller
{
    public function index(Request $request, int $userId): View
    {
        $user = User::findOrFail($userId);
        $plans = Plan::orderBy('order')->get();
        $payments = Payment::where('user_id', $userId)->orderByDesc('datetime')->limit(10)->get();
        $websites = Website::where('user_id', $userId)->latest('created_at')->limit(10)->get();

        return view('admin.users.edit', compact('user', 'plans', 'payments', 'websites'))
            ->with('adminNav', 'users');
    }

    public function update(Request $request, int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'email' => ['required', 'email', 'max:256', 'unique:users,email,'.$userId.',user_id'],
            // status 三态（对标原版）：0=未确认 1=激活 2=禁用
            'status' => ['required', 'integer', 'in:0,1,2'],
            // type 特权：1=管理员 0=普通用户
            'type' => ['required', 'integer', 'in:0,1'],
            'referred_by' => ['nullable', 'integer', 'exists:users,user_id'],
            'plan_id' => ['nullable', 'string', 'max:64'],
            'plan_trial_done' => ['nullable', 'boolean'],
            'plan_expiration_date' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            // 自定义限额（-1 = 不限制，对标原版语义）
            'plan_settings.websites_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.sessions_events_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.sessions_events_retention' => ['nullable', 'integer', 'min:0'],
            'plan_settings.events_children_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.events_children_retention' => ['nullable', 'integer', 'min:0'],
            'plan_settings.sessions_replays_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.sessions_replays_retention' => ['nullable', 'integer', 'min:0'],
            'plan_settings.sessions_replays_time_limit' => ['nullable', 'integer', 'min:0'],
            'plan_settings.websites_heatmaps_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.websites_goals_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.annotations_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.domains_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.dashboard_views_limit' => ['nullable', 'integer', 'min:-1'],
            'plan_settings.affiliate_commission_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        // 权限开关（未勾选不提交 → 显式 false）
        foreach (['email_reports_is_enabled', 'teams_is_enabled', 'no_ads', 'api_is_enabled', 'white_labeling_is_enabled'] as $flag) {
            $request->merge(['plan_settings' => array_merge(
                (array) $request->input('plan_settings', []),
                [$flag => $request->boolean('plan_settings.'.$flag)],
            )]);
        }

        // 导出格式权限（CSV/JSON/PDF 多选）
        $request->merge(['plan_settings' => array_merge(
            (array) $request->input('plan_settings', []),
            ['export' => array_values(array_intersect(['csv', 'json', 'pdf'], (array) $request->input('plan_settings.export', [])))],
        )]);

        // plan_settings 组装：白名单键 → 用户级覆盖保存进 users.plan_settings JSON
        $planKeys = [
            'websites_limit', 'sessions_events_limit', 'sessions_events_retention',
            'events_children_limit', 'events_children_retention',
            'sessions_replays_limit', 'sessions_replays_retention', 'sessions_replays_time_limit',
            'websites_heatmaps_limit', 'websites_goals_limit', 'annotations_limit',
            'domains_limit', 'dashboard_views_limit', 'affiliate_commission_percentage',
            'email_reports_is_enabled', 'teams_is_enabled', 'no_ads', 'api_is_enabled',
            'white_labeling_is_enabled', 'export',
        ];
        $planSettings = collect($request->input('plan_settings', []))
            ->only($planKeys)
            ->toArray();

        // 密码（独立处理，空则不改）
        if (! empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => (int) $validated['status'],
            'type' => (int) $validated['type'],
            'referred_by' => $validated['referred_by'] ?? null,
            'plan_id' => $validated['plan_id'] ?? $user->plan_id,
            'plan_trial_done' => $request->boolean('plan_trial_done'),
            'plan_expiration_date' => $validated['plan_expiration_date'] ?? null,
            'plan_settings' => $planSettings,
            'password' => $validated['password'] ?? $user->password,
        ]);

        return back()->with('success', __('msg.user_updated'));
    }

    public function toggleStatus(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        // 激活 ↔ 禁用 往返切换（对标原版双态切换，未确认态经编辑页调整）
        $user->update(['status' => $user->status === 1 ? 2 : 1]);

        return back()->with('success', __('msg.user_status_toggled'));
    }

    public function loginAs(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        auth()->login($user, true);

        return redirect()->route('dashboard');
    }
}

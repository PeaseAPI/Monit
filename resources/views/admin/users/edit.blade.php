@extends('layouts.admin')
@section('title', __('admin.edit_user'))
@section('content')
@php
    $ps = (array) ($user->plan_settings ?? []);
    $psVal = fn (string $key, $default = '') => old('plan_settings.'.$key, $ps[$key] ?? $default);
    $psBool = fn (string $key) => (bool) old('plan_settings.'.$key, $ps[$key] ?? false);
    $export = (array) old('plan_settings.export', $ps['export'] ?? []);
    $limitFields = [
        'websites_limit' => ['admin.ps_websites_limit', -1],
        'sessions_events_limit' => ['admin.ps_sessions_events_limit', -1],
        'sessions_events_retention' => ['admin.ps_sessions_events_retention', 0],
        'events_children_limit' => ['admin.ps_events_children_limit', -1],
        'events_children_retention' => ['admin.ps_events_children_retention', 0],
        'sessions_replays_limit' => ['admin.ps_sessions_replays_limit', -1],
        'sessions_replays_retention' => ['admin.ps_sessions_replays_retention', 0],
        'sessions_replays_time_limit' => ['admin.ps_sessions_replays_time_limit', 0],
        'websites_heatmaps_limit' => ['admin.ps_websites_heatmaps_limit', -1],
        'websites_goals_limit' => ['admin.ps_websites_goals_limit', -1],
        'annotations_limit' => ['admin.ps_annotations_limit', -1],
        'domains_limit' => ['admin.ps_domains_limit', -1],
        'dashboard_views_limit' => ['admin.ps_dashboard_views_limit', -1],
    ];
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_user') }} · {{ $user->email }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.users.view', $user->user_id) }}" class="btn btn-secondary">{{ __('common.view') }}</a>
        <a href="{{ route('admin.users.login-as', $user->user_id) }}" class="btn btn-ghost" onclick="return confirm('{{ __('admin.login_as_confirm') }}')">{{ __('admin.login_as') }}</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.update', $user->user_id) }}">@csrf @method('PUT')
{{-- ========== 基本信息 ========== --}}
<div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_basic') }}</h2></div>
    <div class="grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <label class="form-label">{{ __('admin.user_id') }}</label>
            <input type="text" value="{{ $user->user_id }}" class="form-input bg-zinc-50" readonly>
        </div>
        <div>
            <label class="form-label">{{ __('admin.user_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('contact.email_label') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('admin.user_status') }}</label>
            <select name="status" class="form-select">
                <option value="1" {{ (string) old('status', $user->status) === '1' ? 'selected' : '' }}>{{ __('admin.status_active') }}</option>
                <option value="0" {{ (string) old('status', $user->status) === '0' ? 'selected' : '' }}>{{ __('admin.status_unconfirmed') }}</option>
                <option value="2" {{ (string) old('status', $user->status) === '2' ? 'selected' : '' }}>{{ __('admin.status_disabled') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('admin.user_type') }}</label>
            <select name="type" class="form-select">
                <option value="0" {{ (string) old('type', $user->type) === '0' ? 'selected' : '' }}>{{ __('admin.type_user') }}</option>
                <option value="1" {{ (string) old('type', $user->type) === '1' ? 'selected' : '' }}>{{ __('admin.type_admin') }}</option>
            </select>
            <p class="form-hint">{{ __('admin.type_admin_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.referred_by') }}</label>
            <input type="number" name="referred_by" value="{{ old('referred_by', $user->referred_by) }}" class="form-input" placeholder="{{ __('common.optional') }}">
        </div>
    </div>
</div>


{{-- ========== 套餐 ========== --}}
<div class="mt-6 rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_plan') }}</h2></div>
    <div class="grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <label class="form-label">{{ __('admin.col_plan') }}</label>
            <select name="plan_id" class="form-select">
                <option value="custom" {{ old('plan_id', $user->plan_id) === 'custom' ? 'selected' : '' }}>{{ __('admin.plan_custom') }}</option>
                @foreach ($plans ?? [] as $p)
                    <option value="{{ $p->plan_id }}" {{ (string) old('plan_id', $user->plan_id) === (string) $p->plan_id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('admin.plan_expiration_date') }}</label>
            <input type="datetime-local" name="plan_expiration_date" value="{{ old('plan_expiration_date', optional($user->plan_expiration_date)->format('Y-m-d\TH:i')) }}" class="form-input">
        </div>
        <div class="sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" name="plan_trial_done" value="1" class="rounded border-zinc-300" {{ old('plan_trial_done', $user->plan_trial_done) ? 'checked' : '' }}>
                {{ __('admin.plan_trial_done') }}
            </label>
        </div>
    </div>
</div>

{{-- ========== 限额（plan_settings） ========== --}}
<div class="mt-6 rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4">
        <h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_limits') }}</h2>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.user_section_limits_hint') }}</p>
    </div>
    <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($limitFields as $key => [$label, $default])
        <div>
            <label class="form-label">{{ __($label) }}</label>
            <input type="number" name="plan_settings[{{ $key }}]" value="{{ $psVal($key, $default) }}" class="form-input">
        </div>
        @endforeach
        <div>
            <label class="form-label">{{ __('admin.ps_affiliate_commission') }}</label>
            <input type="number" name="plan_settings[affiliate_commission_percentage]" min="0" max="100" value="{{ $psVal('affiliate_commission_percentage', '') }}" class="form-input">
        </div>
    </div>
</div>


{{-- ========== 权限 ========== --}}
<div class="mt-6 rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_privileges') }}</h2></div>
    <div class="grid gap-3 p-6 sm:grid-cols-2">
        @foreach (['email_reports_is_enabled', 'teams_is_enabled', 'no_ads', 'api_is_enabled', 'white_labeling_is_enabled'] as $flag)
        <label class="flex items-center gap-2 text-sm text-zinc-700">
            <input type="checkbox" name="plan_settings[{{ $flag }}]" value="1" class="rounded border-zinc-300" {{ $psBool($flag) ? 'checked' : '' }}>
            {{ __('admin.ps_'.$flag) }}
        </label>
        @endforeach
    </div>
    <div class="border-t border-zinc-100 px-6 py-4">
        <p class="form-label">{{ __('admin.ps_export') }}</p>
        <div class="mt-2 flex flex-wrap gap-4">
            @foreach (['csv', 'json', 'pdf'] as $fmt)
            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" name="plan_settings[export][]" value="{{ $fmt }}" class="rounded border-zinc-300" {{ in_array($fmt, $export) ? 'checked' : '' }}>
                {{ strtoupper($fmt) }}
            </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ========== 安全 ========== --}}
<div class="mt-6 rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_security') }}</h2></div>
    <div class="grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <label class="form-label">{{ __('admin.new_password') }}</label>
            <input type="password" name="password" class="form-input" autocomplete="new-password">
        </div>
        <div>
            <label class="form-label">{{ __('admin.repeat_password') }}</label>
            <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
        </div>
    </div>
    <div class="border-t border-zinc-100 px-6 py-4">
        <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
</div>
</form>

{{-- ========== 最近支付 / 网站 ========== --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.recent_payments') }}</h2></div>
        <ul class="divide-y divide-zinc-100">
            @forelse ($payments ?? [] as $pay)
            <li class="flex items-center justify-between gap-3 px-6 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm text-zinc-900">{{ $pay->plan['name'] ?? $pay->plan_id }}</p>
                    <p class="text-xs text-zinc-500">{{ $pay->processor }} · {{ $pay->datetime }}</p>
                </div>
                <span class="text-sm font-medium text-zinc-900">{{ \App\Support\Currency::format((float) $pay->total_amount, $pay->currency) }}</span>
            </li>
            @empty<li class="px-6 py-8 text-center text-sm text-zinc-400">{{ __('common.no_data') }}</li>@endforelse
        </ul>
    </div>
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_websites') }}</h2></div>
        <ul class="divide-y divide-zinc-100">
            @forelse ($websites ?? [] as $site)
            <li class="flex items-center justify-between gap-3 px-6 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm text-zinc-900">{{ $site->name }}</p>
                    <p class="truncate text-xs text-zinc-500">{{ $site->scheme }}://{{ $site->host }}</p>
                </div>
                <span class="text-xs text-zinc-400">{{ optional($site->created_at)->format('Y-m-d') }}</span>
            </li>
            @empty<li class="px-6 py-8 text-center text-sm text-zinc-400">{{ __('common.no_data') }}</li>@endforelse
        </ul>
    </div>
</div>
@endsection

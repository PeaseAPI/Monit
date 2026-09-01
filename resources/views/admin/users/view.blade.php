@extends('layouts.admin')
@section('title', __('admin.user_detail'))
@section('content')
@php
    $billing = $user->billing;
    if (is_string($billing)) { $billing = json_decode($billing, true); }
    $billing = (array) ($billing ?? []);
    $ps = $user->getPlanSettings();
    $statusMeta = match ((int) $user->status) {
        1 => ['bg-emerald-50 text-emerald-700', __('admin.status_active')],
        0 => ['bg-amber-50 text-amber-700', __('admin.status_unconfirmed')],
        default => ['bg-red-50 text-red-700', __('admin.status_disabled')],
    };
    $row = fn (string $label, $value) => '<div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-sm text-zinc-500 shrink-0">'.$label.'</dt><dd class="text-sm text-zinc-900 text-right break-all">'.($value === null || $value === '' ? '-' : $value).'</dd></div>';
@endphp
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.user_detail') }}</h1>
    </div>
    <a href="{{ route('admin.users.edit', $user->user_id) }}" class="btn btn-secondary">{{ __('common.edit') }}</a>
</div>

{{-- ===== 头部档案卡 ===== --}}
<div class="rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm">
    <div class="flex flex-wrap items-center gap-4">
        @if ($user->avatar)
            <img src="{{ $user->avatar }}" alt="" class="h-16 w-16 rounded-2xl object-cover">
        @else
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-2xl font-semibold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
        @endif
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold text-zinc-900">{{ $user->name }}</h2>
                <span class="badge-soft {{ $statusMeta[0] }}">{{ $statusMeta[1] }}</span>
                @if ($user->type == 1)<span class="badge-soft bg-violet-50 text-violet-700">{{ __('admin.type_admin') }}</span>@endif
                <span class="badge-soft bg-brand-50 text-brand-700">{{ $user->plan_id }}</span>
            </div>
            <p class="mt-1 text-sm text-zinc-500">{{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif</p>
        </div>
        <div class="ml-auto grid grid-cols-2 gap-4 text-center sm:grid-cols-3">
            <div><p class="text-2xl font-bold text-zinc-900">{{ number_format($sessions ?? 0) }}</p><p class="text-xs text-zinc-500">{{ __('admin.uv_sessions') }}</p></div>
            <div><p class="text-2xl font-bold text-zinc-900">{{ number_format($totalPageviews ?? 0) }}</p><p class="text-xs text-zinc-500">{{ __('admin.uv_pageviews') }}</p></div>
            <div><p class="text-2xl font-bold text-zinc-900">{{ count($websites ?? []) }}</p><p class="text-xs text-zinc-500">{{ __('admin.user_websites') }}</p></div>
        </div>
    </div>
</div>

{{-- ===== 账户与安全 / 地理与设备 ===== --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.uv_account_security') }}</h2></div>
        <dl class="divide-y divide-zinc-100 px-6">
            {!! $row(__('admin.user_id'), $user->user_id) !!}
            {!! $row(__('admin.col_register_time'), optional($user->created_at)->format('Y-m-d H:i')) !!}
            {!! $row(__('admin.email_verified'), $user->email_verified_at ? __('admin.twofa_on') : __('admin.twofa_off')) !!}
            {!! $row(__('admin.phone_verified'), $user->phone_verified_at ? __('admin.twofa_on') : ($user->phone ? __('admin.status_unconfirmed') : __('admin.twofa_off'))) !!}
            {!! $row(__('admin.uv_twofa'), $user->twofa_is_enabled ? __('admin.twofa_on') : __('admin.twofa_off')) !!}
            {!! $row(__('admin.uv_anti_phishing'), $user->anti_phishing_code ?: '-') !!}
            {!! $row(__('admin.uv_api_key'), $user->api_key) !!}
            {!! $row(__('admin.uv_newsletter'), $user->is_newsletter_subscribed ? __('admin.twofa_on') : __('admin.twofa_off')) !!}
            {!! $row(__('admin.uv_source'), $user->source ?: '-') !!}
            {!! $row(__('admin.uv_total_logins'), $user->total_logins) !!}
            {!! $row(__('admin.uv_last_activity'), optional($user->last_activity)->format('Y-m-d H:i')) !!}
        </dl>
    </div>
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.uv_geo_device') }}</h2></div>
        <dl class="divide-y divide-zinc-100 px-6">
            {!! $row(__('admin.uv_ip'), $user->ip ?: '-') !!}
            {!! $row(__('admin.uv_country'), $user->country ?: '-') !!}
            {!! $row(__('admin.uv_city'), trim(($user->city_name ?: '').' '.($user->country ?: '')) ?: '-') !!}
            {!! $row(__('admin.uv_device'), $user->device_type ?: '-') !!}
            {!! $row(__('admin.uv_os'), $user->os_name ?: '-') !!}
            {!! $row(__('admin.uv_browser'), $user->browser_name ?: '-') !!}
            {!! $row(__('admin.uv_language'), $user->language) !!}
            {!! $row(__('admin.uv_timezone'), $user->timezone) !!}
            {!! $row(__('admin.uv_coordinates'), ($user->latitude && $user->longitude) ? $user->latitude.', '.$user->longitude : '-') !!}
        </dl>
    </div>
</div>


{{-- ===== 套餐与限额 / 支付与推荐 ===== --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.user_section_plan') }}</h2></div>
        <dl class="divide-y divide-zinc-100 px-6">
            {!! $row(__('admin.col_plan'), $user->plan_id.($user->plan_settings ? '（'.__('admin.uv_customized').'）' : '')) !!}
            {!! $row(__('admin.plan_expiration_date'), optional($user->plan_expiration_date)->format('Y-m-d H:i')) !!}
            {!! $row(__('admin.plan_trial_done'), $user->plan_trial_done ? __('admin.twofa_on') : __('admin.twofa_off')) !!}
            @php
                $limitLabels = ['websites_limit','sessions_events_limit','sessions_events_retention','events_children_limit','events_children_retention','sessions_replays_limit','sessions_replays_retention','sessions_replays_time_limit','websites_heatmaps_limit','websites_goals_limit','annotations_limit','domains_limit','dashboard_views_limit'];
            @endphp
            @foreach ($limitLabels as $key)
                {!! $row(__('admin.ps_'.$key), isset($ps[$key]) ? $ps[$key] : '-') !!}
            @endforeach
            @foreach (['email_reports_is_enabled','teams_is_enabled','no_ads','api_is_enabled','white_labeling_is_enabled'] as $flag)
                {!! $row(__('admin.ps_'.$flag), !empty($ps[$flag]) ? __('admin.twofa_on') : __('admin.twofa_off')) !!}
            @endforeach
            {!! $row(__('admin.ps_export'), !empty($ps['export']) ? strtoupper(implode(' / ', (array) $ps['export'])) : '-') !!}
        </dl>
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
            <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.uv_last_payment') }}</h2></div>
            <dl class="divide-y divide-zinc-100 px-6">
                {!! $row(__('admin.uv_payment_processor'), $user->payment_processor ?: '-') !!}
                {!! $row(__('admin.uv_payment_amount'), $user->payment_total_amount ? \App\Support\Currency::format((float) $user->payment_total_amount, $user->payment_currency ?: 'CNY') : '-') !!}
                {!! $row(__('admin.uv_subscription_id'), $user->payment_subscription_id ?: '-') !!}
            </dl>
        </div>
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
            <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.uv_referral') }}</h2></div>
            <dl class="divide-y divide-zinc-100 px-6">
                {!! $row(__('admin.uv_referral_key'), $user->referral_key) !!}
                {!! $row(__('admin.referred_by'), $user->referredBy?->email ?: '-') !!}
                {!! $row(__('admin.uv_referrals_converted'), $user->referrals()->where('referred_by_has_converted', 1)->count()) !!}
                {!! $row(__('admin.uv_referrals_total'), $user->referrals()->count()) !!}
            </dl>
        </div>
        <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
            <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.uv_billing') }}</h2></div>
            <dl class="divide-y divide-zinc-100 px-6">
                @foreach (['name' => 'admin.uv_billing_name', 'address' => 'admin.uv_billing_address', 'city' => 'admin.uv_billing_city', 'county' => 'admin.uv_billing_county', 'zip' => 'admin.uv_billing_zip', 'country' => 'admin.uv_billing_country', 'phone' => 'admin.uv_billing_phone', 'tax_id' => 'admin.uv_billing_tax_id'] as $k => $label)
                    {!! $row(__($label), $billing[$k] ?? '-') !!}
                @endforeach
            </dl>
        </div>
    </div>
</div>

{{-- ===== 最近支付 ===== --}}
<div class="mt-6 rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.recent_payments') }}</h2></div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>{{ __('admin.col_plan') }}</th><th>{{ __('payments.amount') }}</th><th>{{ __('admin.uv_payment_processor') }}</th><th>{{ __('admin.user_status') }}</th><th>{{ __('admin.col_date') }}</th></tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($payments ?? [] as $pay)
                <tr>
                    <td class="text-zinc-900">{{ $pay->plan['name'] ?? $pay->plan_id }}</td>
                    <td>{{ \App\Support\Currency::format((float) $pay->total_amount, $pay->currency ?: 'CNY') }}</td>
                    <td class="text-zinc-500">{{ $pay->processor }}</td>
                    <td class="text-zinc-500">{{ $pay->status }}</td>
                    <td class="text-zinc-500">{{ $pay->datetime }}</td>
                </tr>
                @empty<tr><td colspan="5" class="py-10 text-center text-zinc-400">{{ __('common.no_data') }}</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== 网站 / 账户日志 ===== --}}
<div class="mt-6 grid gap-6 lg:grid-cols-2">
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
    <div class="rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
        <div class="border-b border-zinc-100 px-6 py-4"><h2 class="font-semibold text-zinc-900">{{ __('admin.account_logs') }}</h2></div>
        <ul class="max-h-96 divide-y divide-zinc-100 overflow-y-auto">
            @forelse ($logs ?? [] as $log)
            <li class="flex items-center gap-4 px-6 py-2.5"><span class="text-sm text-zinc-700">{{ $log->message ?? $log->action ?? '-' }}</span><span class="ml-auto shrink-0 text-xs text-zinc-400">{{ $log->datetime }}</span></li>
            @empty<li class="px-6 py-8 text-center text-sm text-zinc-400">{{ __('common.no_data') }}</li>@endforelse
        </ul>
    </div>
</div>
@endsection

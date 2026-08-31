@extends('layouts.admin')

@section('title', __('admin.overview'))

@section('content')
{{-- 统计卡（对标原版：图标块 + 总数 + 本月增量，整卡可点）--}}
@php
    $statMeta = [
        'websites' => ['icon' => 'M3 5h18v14H3zM3 9h18M7 7h.01', 'label' => __('admin.stat_websites')],
        'replays' => ['icon' => 'M15 10.5l5-3v9l-5-3M3 6h12v12H3z', 'label' => __('admin.stat_replays')],
        'heatmaps' => ['icon' => 'M12 3c1 3 4 4 4 8a4 4 0 11-8 0c0-2 1-3 1-5 2 1 3 2 3 3', 'label' => __('admin.stat_heatmaps')],
        'goals' => ['icon' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM12 9v3l2 2M9 12h.01', 'label' => __('admin.stat_goals')],
        'domains' => ['icon' => 'M12 3a9 9 0 100 18 9 9 0 000-18zM3 12h18M12 3a13 13 0 010 18 13 13 0 010-18', 'label' => __('admin.stat_domains')],
        'users' => ['icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 20c0-3 3.6-5 8-5s8 2 8 5', 'label' => __('admin.stat_users')],
        'payments' => ['icon' => 'M3 6h18v12H3zM3 10h18M7 15h4', 'label' => __('admin.stat_payments')],
    ];
@endphp
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($statMeta as $key => $meta)
        <a href="{{ $stats[$key]['route'] }}" class="group relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-500/5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold text-zinc-500">{{ $meta['label'] }}</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-100">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $meta['icon'] }}"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900">{{ number_format($stats[$key]['total']) }}</p>
            <p class="mt-1.5 text-xs text-zinc-400">
                <span class="font-semibold text-emerald-600">+{{ number_format($stats[$key]['month']) }}</span>
                {{ __('admin.stat_this_month') }}
            </p>
        </a>
    @endforeach
    <a href="{{ route('admin.payments.index') }}" class="group relative overflow-hidden rounded-2xl border border-brand-200/60 bg-gradient-to-br from-brand-600 to-brand-700 p-5 text-white transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-600/25">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-semibold text-white/80">{{ __('admin.monthly_revenue') }}</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8c-1.5 0-3 .6-3 2s3 2 3 2 3 .6 3 2-1.5 2-3 2m0-8V6m0 12v-2M3 6h18v12H3z"/></svg>
            </span>
        </div>
        <p class="mt-3 text-3xl font-bold tracking-tight">¥{{ number_format($monthlyRevenue, 2) }}</p>
        <p class="mt-1.5 text-xs text-white/70">{{ __('admin.active_users') }}: {{ number_format($activeUsers) }}</p>
    </a>
</div>

{{-- 最新用户（对标原版 latest users：头像/状态/套餐/注册时间/操作）--}}
<div class="mt-8 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white">
    <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4">
        <h2 class="text-base font-semibold text-zinc-900">{{ __('admin.recent_users') }}</h2>
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('admin.view_all') }} →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50/80 text-left text-xs uppercase tracking-wider text-zinc-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_user') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_status') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_plan') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_register_time') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('admin.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($recentUsers as $user)
                <tr class="transition hover:bg-zinc-50/60">
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700">{{ mb_substr($user->name, 0, 1) }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.view', $user->user_id) }}" class="block truncate font-medium text-zinc-900 hover:text-brand-600">{{ $user->name }}</a>
                                <span class="block truncate text-xs text-zinc-500">{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3.5">
                        @if ($user->status)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('admin.status_active') }}</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __('admin.status_inactive') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5"><span class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">{{ $user->plan_id }}</span></td>
                    <td class="px-6 py-3.5 text-zinc-500">{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-3.5 text-right">
                        <a href="{{ route('admin.users.view', $user->user_id) }}" class="rounded-lg p-2 text-zinc-400 transition hover:bg-brand-50 hover:text-brand-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-10 text-center text-zinc-400">{{ __('admin.no_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- 最近支付 --}}
<div class="mt-8 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white">
    <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4">
        <h2 class="text-base font-semibold text-zinc-900">{{ __('admin.recent_payments') }}</h2>
        <a href="{{ route('admin.payments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('admin.view_all') }} →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50/80 text-left text-xs uppercase tracking-wider text-zinc-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_user') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_plan') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_amount') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($recentPayments as $payment)
                <tr class="transition hover:bg-zinc-50/60">
                    <td class="px-6 py-3.5 font-medium text-zinc-900">{{ optional($payment->user)->name ?? '—' }}</td>
                    <td class="px-6 py-3.5"><span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600">{{ $payment->plan_id }}</span></td>
                    <td class="px-6 py-3.5 font-semibold text-emerald-600">{{ $payment->currency ?? 'CNY' }} {{ number_format((float) $payment->total_amount, 2) }}</td>
                    <td class="px-6 py-3.5 text-zinc-500">{{ optional($payment->datetime)->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-6 py-10 text-center text-zinc-400">{{ __('admin.no_payments') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection


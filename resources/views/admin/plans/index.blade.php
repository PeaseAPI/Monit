@extends('layouts.admin')
@section('title', __('admin.plan_list'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.plan_list') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.plans_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        {{ __('common.add') }}
    </a>
</div>

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('admin.plan_name_col') }}</th>
                    <th>{{ __('admin.plan_price_col') }}</th>
                    <th>{{ __('admin.plans_trial_col') }}</th>
                    <th>{{ __('admin.plans_order_col') }}</th>
                    <th>{{ __('admin.col_status') }}</th>
                    <th class="text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($plans ?? [] as $p)
                @php
                    $monthly = $p->prices['CNY']['monthly'] ?? null;
                    $annual = $p->prices['CNY']['annual'] ?? null;
                @endphp
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="badge-soft bg-brand-50 font-mono text-brand-700">{{ $p->plan_id }}</span>
                            <span class="font-medium text-zinc-900">{{ $p->name }}</span>
                        </div>
                    </td>
                    <td>
                        @if ($monthly !== null && (float) $monthly > 0)
                            <span class="font-semibold text-zinc-900">¥{{ number_format((float) $monthly, ((float) $monthly == (int) $monthly) ? 0 : 1) }}</span>
                            <span class="text-xs text-zinc-400">/{{ __('landing.billing_monthly') }}</span>
                            @if ($annual)
                                <span class="ml-2 text-xs text-zinc-500">¥{{ number_format((float) $annual, 0) }}/{{ __('landing.billing_annual') }}</span>
                            @endif
                        @else
                            <span class="badge-soft bg-emerald-50 text-emerald-700">{{ __('admin.plans_free_forever') }}</span>
                        @endif
                    </td>
                    <td class="text-zinc-500">{{ $p->trial_days > 0 ? $p->trial_days.' '.__('admin.plans_days') : '—' }}</td>
                    <td class="text-zinc-500 tabular-nums">{{ $p->order }}</td>
                    <td>
                        @if ($p->is_enabled)
                            <span class="badge-soft bg-emerald-50 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('msg.status_enabled') }}</span>
                        @else
                            <span class="badge-soft bg-zinc-100 text-zinc-500"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __('msg.status_disabled') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end">
                            <a href="{{ route('admin.plans.edit', $p->plan_id) }}" class="btn btn-secondary px-3 py-1.5 text-xs">{{ __('common.edit') }}</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-400">{{ __('common.no_plans') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', __('admin.statistics_title'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_title') }}</h1><p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_subtitle') }}</p></div>
@php
    $statCards = [
        ['label' => __('admin.total_visits'), 'value' => number_format($totalVisits ?? 0), 'grad' => 'from-brand-500 to-brand-700', 'icon' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => __('admin.daily_avg_visits'), 'value' => number_format($dailyAvg ?? 0), 'grad' => 'from-sky-500 to-blue-600', 'icon' => 'M3 13.5 8.5 8l4 4 8-8M21 4v6m0-6h-6'],
        ['label' => __('admin.total_events'), 'value' => number_format($totalEvents ?? 0), 'grad' => 'from-violet-500 to-purple-600', 'icon' => 'M3.75 3v11.25A2.25 2.25 0 006 16.5h12a2.25 2.25 0 002.25-2.25V3M3.75 3h16.5M12 7.5v5.25m0-5.25l-2.25 2.25M12 7.5l2.25 2.25'],
    ];
@endphp
<div class="grid gap-4 md:grid-cols-3">
    @foreach ($statCards as $card)
        <div class="group relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/5">
            <span class="glow-orb -top-10 -right-10 h-28 w-28 bg-brand-500/10" style="opacity:.5"></span>
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold text-zinc-500">{{ $card['label'] }}</p>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $card['grad'] }} text-white shadow-md shadow-zinc-900/10 transition duration-300 group-hover:scale-110">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $card['icon'] }}"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 tabular-nums">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- 用户地理分布地图（对标原版 admin statistics users_map：供应商由设置决定）--}}
<div class="mt-8">
    <x-user-map :countries="$countries" :points="$points" />
</div>

{{-- 国家分布表（对标原版 countries 表）--}}
<div class="mt-8 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white">
    <div class="border-b border-zinc-100 px-6 py-4"><h2 class="text-base font-semibold text-zinc-900">{{ __('admin.countries_distribution') }}</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50/80 text-left text-xs uppercase tracking-wider text-zinc-500">
                <tr>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_country') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.col_percentage') }}</th>
                    <th class="px-6 py-3 font-semibold">{{ __('admin.stat_users') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @php($countryTotal = max(1, array_sum($countries ?? [])))
                @forelse ($byCountry ?? [] as $row)
                <tr class="transition hover:bg-zinc-50/60">
                    <td class="px-6 py-3 font-medium text-zinc-900">{{ $row->country }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ number_format($row->count / $countryTotal * 100, 2) }}%</td>
                    <td class="px-6 py-3 font-semibold text-zinc-900">{{ number_format($row->count) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-6 py-10 text-center text-zinc-400">{{ __('admin.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

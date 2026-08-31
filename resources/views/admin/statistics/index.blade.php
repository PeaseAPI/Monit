@extends('layouts.admin')
@section('title', __('admin.statistics_title'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_title') }}</h1><p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_subtitle') }}</p></div>
<div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.total_visits') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($totalVisits ?? 0) }}</p></div>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.daily_avg_visits') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($dailyAvg ?? 0) }}</p></div>
    <div class="rounded-2xl border border-zinc-200/80 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.total_events') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($totalEvents ?? 0) }}</p></div>
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

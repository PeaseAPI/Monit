@extends('layouts.guest')

@section('title', $website->name . ' - ' . __('stats.public_stats_title'))
@section('container', 'max-w-7xl')

@section('content')
<div class="py-8">
    {{-- 站点页头 --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 md:text-3xl">{{ $website->name }}</h1>
                <span class="badge-soft bg-brand-50 text-brand-700">{{ __('stats.public_stats_title') }}</span>
            </div>
            <a href="{{ $website->scheme }}://{{ $website->host }}{{ $website->path }}" target="_blank" rel="noopener noreferrer nofollow"
               class="mt-2 inline-flex max-w-full items-center gap-1.5 text-sm text-zinc-500 transition hover:text-brand-600">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                <span class="truncate">{{ $website->host }}{{ $website->path }}</span>
            </a>
        </div>

        {{-- 实时访客徽章 --}}
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200/60 bg-emerald-50/60 px-4 py-2.5">
            <span class="relative flex h-2.5 w-2.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            </span>
            <span class="text-sm font-semibold tabular-nums text-emerald-700">{{ $realtime['count'] ?? 0 }}</span>
            <span class="text-sm text-emerald-600">{{ __('stats.realtime') }}</span>
        </div>
    </div>

    {{-- 时间范围切换 --}}
    <div class="mt-6 mb-8">
        <x-range-switcher :route-name="'statistics.public'" :range="$range" :params="['pixel_key' => $website->pixel_key]" />
    </div>

    {{-- KPI 渐变统计卡 --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $cards = [
                ['label' => __('stats.pageviews'), 'value' => number_format($overview['pageviews'] ?? 0), 'grad' => 'from-sky-500 to-blue-600'],
                ['label' => __('stats.visitors'), 'value' => number_format($overview['visitors'] ?? 0), 'grad' => 'from-violet-500 to-purple-600'],
                ['label' => __('stats.bounce_rate'), 'value' => ($overview['bounce_rate'] ?? 0) . '%', 'grad' => 'from-amber-500 to-orange-600'],
                ['label' => __('stats.realtime'), 'value' => number_format($realtime['count'] ?? 0), 'grad' => 'from-brand-500 to-brand-700'],
            ];
        @endphp
        @foreach ($cards as $card)
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $card['grad'] }}"></div>
            <p class="text-sm font-medium text-zinc-500">{{ $card['label'] }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-zinc-900">{{ $card['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- 趋势图 --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.pageviews_trend') }}</h3>
        @include('components.bar-chart', ['series' => $series])
    </div>

    {{-- 四象限排名 --}}
    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        @include('components.rank-panel', ['title' => __('stats.top_pages'), 'items' => $topPaths])
        @include('components.rank-panel', ['title' => __('stats.top_referrers'), 'items' => $topReferrers])
        @include('components.rank-panel', ['title' => __('stats.top_countries'), 'items' => $topCountries])
        @include('components.rank-panel', ['title' => __('stats.top_devices'), 'items' => $topDevices])
    </div>

    {{-- 页脚标识 --}}
    <div class="mt-10 flex items-center justify-center gap-1.5 text-sm text-zinc-400">
        <span class="relative flex h-1.5 w-1.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-60"></span>
            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-brand-500"></span>
        </span>
        {{ __('stats.powered_by_monit') }}
    </div>
</div>
@endsection
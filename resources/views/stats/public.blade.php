@extends('layouts.guest')

@section('title', $website->name . ' - ' . __('stats.public_stats_title'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- 网站信息头 --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">{{ $website->name }}</h1>
        <p class="text-sm text-gray-500">{{ $website->scheme }}://{{ $website->host }}{{ $website->path }}</p>
    </div>

    {{-- 时间范围切换 --}}
    @include('components.range-switcher', [
        'currentRange' => $range,
        'routeName' => 'statistics.public',
        'routeParams' => ['pixel_key' => $website->pixel_key],
    ])

    {{-- 实时访客 --}}
    <div class="mt-6 mb-8">
        @include('components.stat-card', [
            'label' => __('stats.realtime'),
            'value' => $realtime['count'] ?? 0,
            'icon' => 'eye',
        ])
    </div>

    {{-- KPI 卡片 --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @include('components.stat-card', [
            'label' => __('stats.pageviews'),
            'value' => $overview['pageviews'] ?? 0,
        ])
        @include('components.stat-card', [
            'label' => __('stats.visitors'),
            'value' => $overview['visitors'] ?? 0,
        ])
        @include('components.stat-card', [
            'label' => __('stats.bounce_rate'),
            'value' => ($overview['bounce_rate'] ?? 0) . '%',
        ])
    </div>

    {{-- 趋势图 --}}
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        @include('components.bar-chart', ['series' => $series])
    </div>

    {{-- 四象限 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @include('components.rank-panel', ['title' => __('stats.top_pages'), 'items' => $topPaths])
        @include('components.rank-panel', ['title' => __('stats.top_referrers'), 'items' => $topReferrers])
        @include('components.rank-panel', ['title' => __('stats.top_countries'), 'items' => $topCountries])
        @include('components.rank-panel', ['title' => __('stats.top_devices'), 'items' => $topDevices])
    </div>

    {{-- 页脚标识 --}}
    <div class="mt-8 text-center text-sm text-gray-400">
        {{ __('stats.powered_by_monit') }}
    </div>
</div>
@endsection
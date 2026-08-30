@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.behavior_title')" />
    <x-range-switcher :route-name="'stats.behavior'" :website="$website" :range="$range" />

    {{-- M21 渠道分组（GA 默认渠道分组） --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ($channels as $name => $count)
            <x-stat-card :label="__('stats.channel_'.$name)" :value="number_format($count)" :hint="__('stats.channels_hint')" />
        @endforeach
    </div>

    {{-- M21 时段分析（CNZZ 时段分析） --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.hourly_distribution') }}</h3>
        @php
            $hourSeries = collect($hourly)->map(fn ($h) => [
                'date' => $h['label'],
                'pageviews' => $h['pageviews'],
                'visitors' => $h['visitors'],
            ])->all();
        @endphp
        <x-bar-chart :series="$hourSeries" />
    </div>

    {{-- M22 星期分布（原版 weekdays 页） --}}
    <div class="mt-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.weekday_distribution') }}</h3>
        @php
            $weekdaySeries = collect($weekdays)->map(fn ($d) => [
                'date' => $d['label'],
                'pageviews' => $d['pageviews'],
                'visitors' => $d['visitors'],
            ])->all();
        @endphp
        <x-bar-chart :series="$weekdaySeries" />
    </div>

    {{-- M21 忠诚度（CNZZ 忠诚度 / GA 新访回访） --}}
    <div class="mt-6 grid grid-cols-2 gap-4">
        <x-stat-card :label="__('stats.new_visitors')" :value="number_format($loyalty['new_visitors'])" :hint="__('stats.loyalty_frequency')" />
        <x-stat-card :label="__('stats.returning_visitors')" :value="number_format($loyalty['returning_visitors'])" :hint="__('stats.loyalty_frequency')" />
    </div>
    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <x-rank-panel :title="__('stats.loyalty_frequency')" :items="$loyalty['frequency']" />
        <x-rank-panel :title="__('stats.loyalty_depth')" :items="$loyalty['depth']" />
        <x-rank-panel :title="__('stats.loyalty_duration')" :items="$loyalty['duration']" />
    </div>

    {{-- M21 入口页 / 离开页 --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-rank-panel :title="__('stats.landing_pages')" :items="$landingPages" :show-rank="true" />
        <x-rank-panel :title="__('stats.exit_pages')" :items="$exitPages" :show-rank="true" />
    </div>

    {{-- M21 搜索词 --}}
    @php
        $termItems = collect($searchTerms)->map(fn ($t) => [
            'key' => $t['engines'] ? $t['key'].' ('.$t['engines'].')' : $t['key'],
            'count' => $t['count'],
        ])->all();
    @endphp
    <div class="mt-6">
        <x-rank-panel :title="__('stats.search_terms')" :items="$termItems" :show-rank="true" />
    </div>
</div>
@endsection
@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.events_title')" />
    <x-range-switcher :route-name="'stats.events'" :website="$website" :range="$range" />
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card :label="__('stats.pageviews')" :value="number_format($overview['pageviews'])" />
        <x-stat-card :label="__('stats.visitors')" :value="number_format($overview['visitors'])" />
        <x-stat-card :label="__('stats.sessions')" :value="number_format($overview['sessions'])" />
        <x-stat-card :label="__('stats.bounce_rate')" :value="$overview['bounce_rate'].'%'" />
        <x-stat-card :label="__('stats.avg_duration')" :value="$overview['avg_duration'] > 0 ? gmdate('i:s', $overview['avg_duration']) : '-'" />
    </div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.pageviews_trend') }}</h3>
        <x-bar-chart :series="$series" />
    </div>
    <x-rank-panel class="mt-6" :title="__('stats.events_by_type')" :items="$eventsByType" :show-rank="true" />
</div>
@endsection
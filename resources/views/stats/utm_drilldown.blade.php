@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.utm_drilldown_title', ['source' => $source])" />
    <x-range-switcher :route-name="'stats.utm_drilldown'" :website="$website" :range="$range" :params="['source' => $source]" />
    <div class="mt-4">
        <x-rank-panel :title="__('stats.utm_medium_campaign', ['source' => $source])" :items="$items" :show-rank="true" />
    </div>
</div>
@endsection

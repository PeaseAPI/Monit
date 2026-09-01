@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_browsers_title')" />
    <x-range-switcher :route-name="'stats.top_browsers'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.browser_distribution')" :items="$topBrowsers" :show-rank="true" />
</div>
@endsection
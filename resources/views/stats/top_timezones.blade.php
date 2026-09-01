@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_timezones_title')" />
    <x-range-switcher :route-name="'stats.top_timezones'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.timezone_distribution')" :items="$topTimezones" :show-rank="true" />
</div>
@endsection

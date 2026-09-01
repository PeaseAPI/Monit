@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_devices_title')" />
    <x-range-switcher :route-name="'stats.top_devices'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.device_distribution')" :items="$topDevices" :show-rank="true" />
</div>
@endsection
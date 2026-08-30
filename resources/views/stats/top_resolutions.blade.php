@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.top_resolutions_title')" />
    <x-range-switcher :route-name="'stats.top_resolutions'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.resolution_distribution')" :items="$topResolutions" :show-rank="true" />
</div>
@endsection
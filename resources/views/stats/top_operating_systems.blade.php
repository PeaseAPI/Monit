@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_os_title')" />
    <x-range-switcher :route-name="'stats.top_os'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.os_distribution')" :items="$topOs" :show-rank="true" />
</div>
@endsection
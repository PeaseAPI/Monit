@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_pages_title')" />
    <x-range-switcher :route-name="'stats.top_pages'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.page_ranking')" :items="$topPaths" :show-rank="true" />
</div>
@endsection
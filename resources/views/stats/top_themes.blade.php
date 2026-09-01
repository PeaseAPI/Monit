@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_themes_title')" />
    <x-range-switcher :route-name="'stats.top_themes'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.theme_distribution')" :items="$topThemes" :show-rank="true" />
</div>
@endsection

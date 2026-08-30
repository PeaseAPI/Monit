@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.top_continents_title')" />
    <x-range-switcher :route-name="'stats.top_continents'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.continent_distribution')" :items="$topContinents" :show-rank="true" />
</div>
@endsection

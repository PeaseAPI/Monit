@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.top_cities_title')" />
    <x-range-switcher :route-name="'stats.top_cities'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.city_distribution')" :items="$topCities" :show-rank="true" />
</div>
@endsection
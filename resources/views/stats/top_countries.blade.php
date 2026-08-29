@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.top_countries_title')" />
    <x-range-switcher :route-name="'stats.top_countries'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.country_distribution')" :items="$topCountries" :show-rank="true" />
</div>
@endsection
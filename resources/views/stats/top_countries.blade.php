@extends('layouts.app')
@section('content')
@php
    $countryMapData = collect($topCountries)->mapWithKeys(fn($item) => [$item['key'] => ['visitors' => $item['count']]]);
@endphp
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.top_countries_title')" />
    <x-range-switcher :route-name="'stats.top_countries'" :website="$website" :range="$range" />

        {{-- World map visualization --}}
    <div class="mt-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.country_distribution') }}</h3>
        <div id="svgMapCountries" class="mt-4"
             data-values="{{ json_encode($countryMapData) }}"></div>
    </div>

    <x-rank-panel :title="__('stats.country_distribution')" :items="$topCountries" :show-rank="true" />
</div>

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/svgmap/svgMap.min.css') }}">
@endpush
@endonce

@once
@push('scripts')
<script src="{{ asset('vendor/svgmap/svgMap.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('svgMapCountries');
    var countryValues = JSON.parse(el.getAttribute('data-values'));
    var mapData = {
        data: {
            visitors: {
                name: 'Visitors',
                format: '{0}',
                thousandSeparator: ','
            }
        },
        applyDataParameters: {},
        values: countryValues
    };
    new svgMap({
        targetElementID: 'svgMapCountries',
        data: mapData,
        minZoom: 1,
        maxZoom: 5,
        colorMax: '#6366f1',
        colorNoData: '#f4f4f5'
    });
});
</script>
@endpush
@endonce
@endsection
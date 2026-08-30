@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.outbound_click_paths_title', ['host' => $host])" />
    <x-range-switcher :route-name="'stats.outbound_click_paths'" :website="$website" :range="$range" :params="['host' => $host]" />
    <div class="mt-4">
        <a href="{{ route('stats.outbound_clicks', $website) }}" class="text-sm text-brand-600 hover:underline">← {{ __('stats.back_to_outbound_clicks') }}</a>
    </div>
    <div class="mt-4">
        <x-rank-panel :title="__('stats.outbound_click_paths_of', ['host' => $host])" :items="$paths" :show-rank="true" />
    </div>
</div>
@endsection

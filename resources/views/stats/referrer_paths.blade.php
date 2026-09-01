@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.referrer_paths_title', ['host' => $host])" />
    <x-range-switcher :route-name="'stats.referrer_paths'" :website="$website" :range="$range" :params="['host' => $host]" />
    <div class="mt-4">
        <a href="{{ route('stats.referrers', $website) }}" class="text-sm text-brand-600 hover:underline">← {{ __('stats.back_to_referrers') }}</a>
    </div>
    <div class="mt-4">
        <x-rank-panel :title="__('stats.referrer_paths_of', ['host' => $host])" :items="$paths" :show-rank="true" />
    </div>
</div>
@endsection

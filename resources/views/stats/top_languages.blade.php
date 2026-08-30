@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.top_languages_title')" />
    <x-range-switcher :route-name="'stats.top_languages'" :website="$website" :range="$range" />
    <x-rank-panel :title="__('stats.language_distribution')" :items="$topLanguages" :show-rank="true" />
</div>
@endsection
@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="mb-6"><a href="{{ route('heatmaps.index', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_heatmap_list') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $heatmap->name }}</h1></div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-8 text-center"><p class="text-zinc-500">{{ __('stats.heatmap_render_area') }}</p><p class="mt-2 text-sm text-zinc-400">{{ __('stats.heatmap_click_data') }}: {{ $clicks->count() ?? 0 }} {{ __('stats.groups') }}, {{ __('stats.heatmap_scroll_data') }}: {{ count($scrolls ?? []) }} {{ __('stats.groups') }}</p></div>
</div>
@endsection
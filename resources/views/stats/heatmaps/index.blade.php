@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.heatmaps_title')" :back-route="'stats.index'">
        <a href="{{ route('stats.heatmaps.create', $website->website_id) }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('stats.create_heatmap') }}</a>
    </x-stats-header>
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.heatmap_name') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.heatmap_path') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.heatmap_status') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($heatmaps ?? [] as $h)<tr><td class="px-6 py-3"><a href="{{ route('stats.heatmaps.show', [$website->website_id, $h->heatmap_id]) }}" class="text-brand-600 hover:underline">{{ $h->name }}</a></td><td class="px-6 py-3 text-zinc-500">{{ $h->path }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $h->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $h->is_enabled ? __('stats.goal_enabled') : __('stats.goal_disabled') }}</span></td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('stats.no_heatmaps') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
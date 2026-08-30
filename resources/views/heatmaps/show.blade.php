@extends('layouts.app')
@section('title', __('stats.heatmap_detail'))
@section('content')
<div class="py-8">
    <div class="mb-6"><a href="{{ route('heatmaps.index', $website) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $heatmap->name }}</h1></div>

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.heatmap_path') }}</div><div class="mt-1 text-sm font-mono text-zinc-900">{{ $heatmap->path }}</div></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.device_type') }}</div><div class="mt-1 text-sm text-zinc-900">{{ $heatmap->device_type ?? 'desktop' }}</div></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.snapshots') }}</div><div class="mt-1 text-sm text-zinc-900">{{ $heatmap->snapshots_count ?? $snapshots->count() }}</div></div>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.snapshots') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">ID</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.clicks') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.scroll_depth') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.date') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($snapshots as $snapshot)
                    <tr>
                        <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $snapshot->snapshot_id }}</td>
                        <td class="px-6 py-3 text-zinc-700">{{ $snapshot->clicks_count ?? $snapshot->clicks()->count() }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $snapshot->avg_scroll_depth ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $snapshot->datetime }}</td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="4">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
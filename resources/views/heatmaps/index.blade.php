@extends('layouts.app')
@section('title', __('stats.heatmaps'))
@section('content')
<div class="py-8">
    @include('components.stats-header', ['website' => $website, 'range' => $range ?? 7])

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.heatmaps') }}</h2>
        <a href="{{ route('heatmaps.create', $website) }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.heatmap_name') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.heatmap_path') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.snapshots') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.device_type') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th>
                    <th class="px-6 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($heatmaps as $heatmap)
                    <tr class="hover:bg-zinc-50 cursor-pointer" onclick="window.location='{{ route('heatmaps.show', [$website, $heatmap]) }}'">
                        <td class="px-6 py-3 font-medium text-zinc-900">{{ $heatmap->name }}</td>
                        <td class="px-6 py-3 text-zinc-500 font-mono text-xs">{{ $heatmap->path }}</td>
                        <td class="px-6 py-3 text-zinc-700">{{ $heatmap->snapshots_count ?? $heatmap->snapshots()->count() }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $heatmap->device_type ?? 'desktop' }}</td>
                        <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $heatmap->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $heatmap->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td>
                        <td class="px-6 py-3 text-right">
                            <form method="POST" action="{{ route('heatmaps.destroy', [$website, $heatmap]) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="event.stopPropagation();return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
                        </td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
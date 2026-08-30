@extends('layouts.app')
@section('title', __('stats.replays'))
@section('content')
<div class="py-8">
    @include('components.stats-header', ['website' => $website, 'range' => $range ?? 7])

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @include('components.stat-card', ['label' => __('stats.total_replays'), 'value' => $replays->total()])
        @include('components.stat-card', ['label' => __('stats.avg_duration'), 'value' => $overview['avg_duration'] ?? '0:00'])
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.replay_list') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">ID</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.visitor') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.duration') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.events') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.date') }}</th>
                    <th class="px-6 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($replays as $replay)
                    <tr class="hover:bg-zinc-50">
                        <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $replay->replay_id }}</td>
                        <td class="px-6 py-3 text-zinc-700">{{ $replay->visitor_id }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $replay->duration ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $replay->total_events ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $replay->datetime }}</td>
                        <td class="px-6 py-3 text-right"><a href="{{ route('replays.show', [$website, $replay]) }}" class="text-sm text-brand-600 hover:underline">{{ __('stats.play') }}</a></td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $replays->links() }}
</div>
@endsection
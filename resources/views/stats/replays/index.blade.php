@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.replays_title')" />
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_visitor_id') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.device_type') }}</th>
                                        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.browser') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.os') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.country') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_page') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_time') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($replays ?? [] as $r)
                <tr>
                    <td class="px-6 py-3 font-mono text-xs">{{ $r->visitor_id }}</td>
                    <td class="px-6 py-3 text-sm">{{ ['desktop' => __('dashboard.device_desktop'), 'mobile' => __('dashboard.device_mobile'), 'tablet' => __('dashboard.device_tablet')][$r->visitor?->device_type ?? ''] ?? $r->visitor?->device_type ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ $r->visitor?->browser_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ $r->visitor?->os_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ $r->visitor?->country_code ?? '—' }}</td>
                    <td class="px-6 py-3"><a href="{{ route('stats.replays.show', [$website->website_id, $r->replay_id]) }}" class="text-brand-600 hover:underline">{{ __('stats.view_replay') }}</a></td>
                    <td class="px-6 py-3 text-zinc-500">{{ $r->datetime?->format('Y-m-d H:i:s') }}</td>
                </tr>
                @empty
                <tr><td class="px-6 py-8 text-center text-zinc-500" colspan="7">{{ __('stats.no_replays') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $replays->links() ?? '' }}
</div>
@endsection
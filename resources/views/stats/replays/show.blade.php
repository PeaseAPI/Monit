@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6"><a href="{{ route('replays.index', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_replays') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.replay_detail_title') }}</h1></div>

    @php
        $visitor = $replay->visitor;
        $session = $replay->session;
        $events = $session?->events ?? collect();
    @endphp

    {{-- 访客信息 --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 mb-4">
        <h2 class="text-lg font-semibold text-zinc-800 mb-3">{{ __('stats.replay_visitor_info') }}</h2>
        <dl class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.replay_visitor_id') }}</dt>
                <dd class="mt-1 font-mono text-sm">{{ $replay->visitor_id }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.replay_time') }}</dt>
                <dd class="mt-1 text-sm text-zinc-600">{{ $replay->datetime?->format('Y-m-d H:i:s') }}</dd>
            </div>
            @if($visitor)
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.device_type') }}</dt>
                <dd class="mt-1 text-sm">{{ ['desktop' => __('dashboard.device_desktop'), 'mobile' => __('dashboard.device_mobile'), 'tablet' => __('dashboard.device_tablet')][$visitor->device_type ?? ''] ?? $visitor->device_type ?? '—' }}</dd>
            </div>
            <div>
                                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.os') }}</dt>
                <dd class="mt-1 text-sm">{{ $visitor->os_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.browser') }}</dt>
                <dd class="mt-1 text-sm">{{ $visitor->browser_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('stats.country') }}</dt>
                <dd class="mt-1 text-sm">{{ $visitor->country_code ?? '—' }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- 会话事件列表 --}}
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto mb-4">
        <div class="px-6 py-4 border-b border-zinc-100">
            <h2 class="text-lg font-semibold text-zinc-800">{{ __('stats.replay_events') }} ({{ $events->count() }})</h2>
        </div>
        @if($events->isNotEmpty())
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">#</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_page') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_event_type') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_time') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach($events as $i => $event)
                <tr>
                    <td class="px-6 py-3 text-zinc-400">{{ $i + 1 }}</td>
                    <td class="px-6 py-3 max-w-xs truncate" title="{{ $event->path }}">{{ $event->title ?? $event->path ?? '—' }}</td>
                    <td class="px-6 py-3"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $event->type === 'pageview' ? 'bg-blue-50 text-blue-700' : 'bg-zinc-100 text-zinc-600' }}">{{ $event->type }}</span></td>
                    <td class="px-6 py-3 text-zinc-500">{{ $event->date?->format('H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-6 py-8 text-center text-zinc-500">{{ __('stats.no_replay_events') }}</div>
        @endif
    </div>

    {{-- 回放播放器区域 --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-8 text-center">
        <p class="text-zinc-500">{{ __('stats.replay_player_area') }}</p>
    </div>
</div>
@endsection
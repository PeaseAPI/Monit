@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6"><a href="{{ route('stats.replays', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_replays') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.replay_detail_title') }}</h1></div>

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

        {{-- 回放播放器区域（rrweb-player）--}}
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-800">{{ __('stats.replay_player') }}</h2>
            <div class="flex items-center gap-2" id="replay-controls" style="display:none">
                <button id="replay-play" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-700">▶ {{ __('stats.replay_play') }}</button>
                <button id="replay-pause" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50" style="display:none">⏸ {{ __('stats.replay_pause') }}</button>
                <select id="replay-speed" class="rounded-lg border border-zinc-200 px-2 py-1 text-xs text-zinc-600">
                    <option value="1">1x</option>
                    <option value="2">2x</option>
                    <option value="4">4x</option>
                    <option value="8">8x</option>
                </select>
            </div>
        </div>
        <div id="replay-container" class="relative bg-zinc-900" style="min-height:480px" data-events-url="{{ route('stats.replays.events', [$website->website_id, $replay->replay_id]) }}">
            <div id="replay-loading" class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <svg class="mx-auto h-8 w-8 animate-spin text-brand-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <p class="mt-3 text-sm text-zinc-400">{{ __('stats.replay_loading') }}</p>
                </div>
            </div>
            <div id="replay-empty" class="absolute inset-0 flex items-center justify-center" style="display:none">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-600" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    <p class="mt-3 text-sm text-zinc-400">{{ __('stats.no_replay_events') }}</p>
                </div>
            </div>
            <canvas id="replay-canvas" style="width:100%;display:none"></canvas>
        </div>
    </div>
</div>

{{-- rrweb-player 自托管 + 初始化（国内 CDN 访问不稳定，改用同源静态资源） --}}
<link rel="stylesheet" href="{{ asset('assets/pixel/rrweb-player.min.css') }}">
<script src="{{ asset('assets/pixel/rrweb-player.min.js') }}"></script>
<script>
(function () {
    const eventsUrl = document.getElementById('replay-container').dataset.eventsUrl;
    const loading = document.getElementById('replay-loading');
    const empty = document.getElementById('replay-empty');
    const controls = document.getElementById('replay-controls');
    const container = document.getElementById('replay-container');

    fetch(eventsUrl)
        .then(r => r.ok ? r.json() : [])
        .then(events => {
            loading.style.display = 'none';
            if (!events || events.length === 0) {
                empty.style.display = '';
                return;
            }
            controls.style.display = '';

            // 创建 rrweb-player 目标 DOM
            const playerRoot = document.createElement('div');
            playerRoot.style.width = '100%';
            container.appendChild(playerRoot);

            try {
                const player = new rrwebPlayer({
                    target: playerRoot,
                    props: {
                        events: events,
                        width: container.clientWidth,
                        height: 480,
                        autoPlay: false,
                        showController: true,
                        UNSAFE_replayCanvas: true,
                    },
                });

                // 播放/暂停按钮
                const playBtn = document.getElementById('replay-play');
                const pauseBtn = document.getElementById('replay-pause');
                playBtn.addEventListener('click', () => { player.play(); playBtn.style.display='none'; pauseBtn.style.display=''; });
                pauseBtn.addEventListener('click', () => { player.pause(); pauseBtn.style.display='none'; playBtn.style.display=''; });

                // 速度选择
                document.getElementById('replay-speed').addEventListener('change', function () {
                    player.setSpeed(Number(this.value));
                });
            } catch (e) {
                console.error('rrweb-player init failed:', e);
                empty.style.display = '';
            }
        })
        .catch(() => { loading.style.display = 'none'; empty.style.display = ''; });
})();
</script>
@endsection
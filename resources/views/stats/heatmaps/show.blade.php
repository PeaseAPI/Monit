@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6">
        <a href="{{ route('stats.heatmaps', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_heatmap_list') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $heatmap->name }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ $heatmap->path }}</p>
    </div>
    {{-- Heatmap type tabs --}}
    <div class="mb-4 flex gap-2">
        <button id="tab-clicks" onclick="switchTab('clicks')" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('stats.heatmap_click_data') }}</button>
        <button id="tab-scrolls" onclick="switchTab('scrolls')" class="rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200">{{ __('stats.heatmap_scroll_data') }}</button>
    </div>
    {{-- Device selector --}}
    <div class="mb-4 flex gap-2">
        @foreach(['desktop' => '🖥', 'tablet' => '📱', 'mobile' => '📲'] as $d => $icon)
            <a href="{{ route('stats.heatmaps.show', [$website->website_id, $heatmap->heatmap_id]) }}?device={{ $d }}"
               class="rounded-lg px-3 py-1.5 text-sm {{ $device === $d ? 'bg-brand-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200' }}">
                {{ $icon }} {{ ucfirst($d) }}
            </a>
        @endforeach
    </div>
    {{-- Click heatmap --}}
    <div id="panel-clicks" class="rounded-2xl border border-zinc-200 bg-white">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-3">
            <p class="text-sm text-zinc-500">{{ __('stats.heatmap_click_data') }}: {{ $clicks->count() }} {{ __('stats.groups') }}</p>
        </div>
        <div class="relative" style="min-height:400px">
            <div id="click-replayer-root" class="pointer-events-none" style="min-height:400px"></div>
            <canvas id="click-canvas" class="absolute inset-0 h-full w-full" style="pointer-events:none;z-index:10"></canvas>
            <div id="click-no-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50 {{ $hasSnapshot ? 'hidden' : '' }}">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    <p class="mt-3 text-sm text-zinc-400">{{ __('stats.no_heatmaps') }}</p>
                </div>
            </div>
        </div>
    </div>
    {{-- Scroll heatmap --}}
    <div id="panel-scrolls" class="hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-3">
            <p class="text-sm text-zinc-500">{{ __('stats.heatmap_scroll_data') }}: {{ count($scrolls) }} {{ __('stats.groups') }}</p>
        </div>
        <div class="relative" style="min-height:560px">
            <div id="scroll-replayer-root" class="pointer-events-none" style="min-height:560px"></div>
            <canvas id="scroll-canvas" class="absolute inset-0 h-full w-full" style="pointer-events:none;z-index:10"></canvas>
            <div id="scroll-no-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50 {{ $hasSnapshot ? 'hidden' : '' }}">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    <p class="mt-3 text-sm text-zinc-400">{{ __('stats.no_scroll_data') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('assets/pixel/rrweb-player.min.css') }}">
<script src="{{ asset('assets/pixel/rrweb-player.min.js') }}"></script>
<script id="json-clicks" type="application/json">@json($clicks)</script>
<script id="json-scrolls" type="application/json">@json($scrolls)</script>
<script>
const clicksData = JSON.parse(document.getElementById('json-clicks').textContent);
const scrollsData = JSON.parse(document.getElementById('json-scrolls').textContent);
const snapshotUrl = '{{ route("stats.heatmaps.snapshot", [$website->website_id, $heatmap->heatmap_id]) }}?device={{ $device }}';
let clickReplayer = null;
let scrollReplayer = null;

function switchTab(tab) {
    document.getElementById('panel-clicks').classList.toggle('hidden', tab !== 'clicks');
    document.getElementById('panel-scrolls').classList.toggle('hidden', tab !== 'scrolls');
    const clickBtn = document.getElementById('tab-clicks');
    const scrollBtn = document.getElementById('tab-scrolls');
    if (tab === 'clicks') {
        clickBtn.className = 'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white';
        scrollBtn.className = 'rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200';
        drawClickHeatmap();
    } else {
        scrollBtn.className = 'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white';
        clickBtn.className = 'rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200';
        drawScrollHeatmap();
    }
}

function initSnapshotReplayer(containerId, onReady) {
    fetch(snapshotUrl)
        .then(r => r.ok ? r.json() : [])
        .then(snapshotData => {
            if (!snapshotData || !Array.isArray(snapshotData) || snapshotData.length === 0) {
                if (onReady) onReady(null);
                return;
            }
            const container = document.getElementById(containerId);
            if (!container) { if (onReady) onReady(null); return; }
            const playerRoot = document.createElement('div');
            playerRoot.style.width = '100%';
            container.appendChild(playerRoot);
            try {
                const replayer = new rrwebPlayer({
                    target: playerRoot,
                    props: { events: snapshotData, width: container.clientWidth || 1024, height: 600, autoPlay: true, showController: false, UNSAFE_replayCanvas: true },
                });
                setTimeout(() => { try { replayer.pause(); } catch(e) {} if (onReady) onReady(replayer); }, 500);
                return replayer;
            } catch (e) { console.warn('rrwebPlayer init failed:', e); if (onReady) onReady(null); return null; }
        })
        .catch(() => { if (onReady) onReady(null); });
}

function drawClickHeatmap() {
    const canvas = document.getElementById('click-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;
    canvas.width = container.offsetWidth; canvas.height = container.offsetHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!clicksData.length) { ctx.fillStyle = '#a1a1aa'; ctx.font = '14px sans-serif'; ctx.textAlign = 'center'; ctx.fillText('{{ __("stats.no_click_data") }}', canvas.width / 2, canvas.height / 2); return; }
    const maxCount = Math.max(...clicksData.map(c => parseInt(c.count) || 1));
    clicksData.forEach(point => {
        const x = parseFloat(point.x_normalized) / 100 * canvas.width;
        const y = parseFloat(point.y_normalized) / 100 * canvas.height;
        const intensity = (parseInt(point.count) || 1) / maxCount;
        const radius = Math.max(12, 30 * intensity);
        const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
        gradient.addColorStop(0, `rgba(239, 68, 68, ${0.4 + 0.6 * intensity})`);
        gradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
        ctx.fillStyle = gradient; ctx.beginPath(); ctx.arc(x, y, radius, 0, Math.PI * 2); ctx.fill();
    });
}

function drawScrollHeatmap() {
    const canvas = document.getElementById('scroll-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const container = canvas.parentElement;
    canvas.width = container.offsetWidth; canvas.height = container.offsetHeight;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const entries = Object.entries(scrollsData);
    if (!entries.length) { ctx.fillStyle = '#a1a1aa'; ctx.font = '14px sans-serif'; ctx.textAlign = 'center'; ctx.fillText('{{ __("stats.no_scroll_data") }}', canvas.width / 2, canvas.height / 2); return; }
    const maxCount = Math.max(...entries.map(e => e[1]));
    const barHeight = Math.max(4, canvas.height / 100);
    entries.forEach(([scrollPct, count]) => {
        const y = (parseInt(scrollPct) / 100) * canvas.height;
        const width = (count / maxCount) * canvas.width * 0.8;
        const intensity = count / maxCount;
        const gradient = ctx.createLinearGradient(0, y, width, y);
        gradient.addColorStop(0, `rgba(59, 130, 246, ${0.3 + 0.5 * intensity})`);
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');
        ctx.fillStyle = gradient; ctx.fillRect(0, y - barHeight / 2, width, barHeight);
    });
    ctx.fillStyle = '#71717a'; ctx.font = '11px sans-serif'; ctx.textAlign = 'right';
    for (let pct = 0; pct <= 100; pct += 25) { const y = (pct / 100) * canvas.height; ctx.fillText(pct + '%', canvas.width - 8, y + 4); }
}

window.addEventListener('load', () => {
    clickReplayer = initSnapshotReplayer('click-replayer-root', () => { drawClickHeatmap(); });
    scrollReplayer = initSnapshotReplayer('scroll-replayer-root', () => { drawScrollHeatmap(); });
});
window.addEventListener('resize', () => { drawClickHeatmap(); drawScrollHeatmap(); });
</script>
@endsection
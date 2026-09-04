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

    {{-- Click heatmap --}}
    <div id="panel-clicks" class="rounded-2xl border border-zinc-200 bg-white">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-3">
            <p class="text-sm text-zinc-500">{{ __('stats.heatmap_click_data') }}: {{ $clicks->count() }} {{ __('stats.groups') }}</p>
        </div>
        <div class="relative" style="height:560px">
            <canvas id="click-canvas" class="h-full w-full"></canvas>
        </div>
    </div>

    {{-- Scroll heatmap --}}
    <div id="panel-scrolls" class="hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-3">
            <p class="text-sm text-zinc-500">{{ __('stats.heatmap_scroll_data') }}: {{ count($scrolls) }} {{ __('stats.groups') }}</p>
        </div>
        <div class="relative" style="height:560px">
            <canvas id="scroll-canvas" class="h-full w-full"></canvas>
        </div>
    </div>
</div>

<script id="json-clicks" type="application/json">@json($clicks)</script>
<script id="json-scrolls" type="application/json">@json($scrolls)</script>
<script>
const clicksData = JSON.parse(document.getElementById('json-clicks').textContent);
const scrollsData = JSON.parse(document.getElementById('json-scrolls').textContent);

function switchTab(tab) {
    document.getElementById('panel-clicks').classList.toggle('hidden', tab !== 'clicks');
    document.getElementById('panel-scrolls').classList.toggle('hidden', tab !== 'scrolls');
    const clickBtn = document.getElementById('tab-clicks');
    const scrollBtn = document.getElementById('tab-scrolls');
    if (tab === 'clicks') {
        clickBtn.className = 'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white';
        scrollBtn.className = 'rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200';
    } else {
        scrollBtn.className = 'rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white';
        clickBtn.className = 'rounded-lg bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-200';
    }
}

function drawClickHeatmap() {
    const canvas = document.getElementById('click-canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#fafafa';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    if (!clicksData.length) {
        ctx.fillStyle = '#a1a1aa';
        ctx.font = '14px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('{{ __("stats.no_click_data") }}', canvas.width / 2, canvas.height / 2);
        return;
    }

    const maxCount = Math.max(...clicksData.map(c => parseInt(c.count) || 1));

    clicksData.forEach(point => {
        const x = parseFloat(point.x_normalized) * canvas.width;
        const y = parseFloat(point.y_normalized) * canvas.height;
        const intensity = (parseInt(point.count) || 1) / maxCount;
        const radius = Math.max(8, 20 * intensity);

        const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
        gradient.addColorStop(0, `rgba(239, 68, 68, ${0.3 + 0.7 * intensity})`);
        gradient.addColorStop(1, 'rgba(239, 68, 68, 0)');
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.fill();
    });
}

function drawScrollHeatmap() {
    const canvas = document.getElementById('scroll-canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#fafafa';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const entries = Object.entries(scrollsData);
    if (!entries.length) {
        ctx.fillStyle = '#a1a1aa';
        ctx.font = '14px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('{{ __("stats.no_scroll_data") }}', canvas.width / 2, canvas.height / 2);
        return;
    }

    const maxScroll = Math.max(...entries.map(e => parseInt(e[0])));
    const maxCount = Math.max(...entries.map(e => e[1]));
    const barHeight = canvas.height / 100;

    entries.forEach(([scrollPct, count]) => {
        const y = (parseInt(scrollPct) / 100) * canvas.height;
        const width = (count / maxCount) * canvas.width * 0.8;
        const intensity = count / maxCount;

        const gradient = ctx.createLinearGradient(0, y, width, y);
        gradient.addColorStop(0, `rgba(59, 130, 246, ${0.3 + 0.5 * intensity})`);
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, y - barHeight / 2, width, barHeight);
    });

    // Scroll percentage labels
    ctx.fillStyle = '#71717a';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';
    for (let pct = 0; pct <= 100; pct += 25) {
        const y = (pct / 100) * canvas.height;
        ctx.fillText(pct + '%', canvas.width - 8, y + 4);
    }
}

window.addEventListener('load', () => {
    drawClickHeatmap();
    drawScrollHeatmap();
});
window.addEventListener('resize', () => {
    drawClickHeatmap();
    drawScrollHeatmap();
});
</script>
@endsection
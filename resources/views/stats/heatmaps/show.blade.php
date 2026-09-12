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
                                    <div id="click-no-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50 {{ ($hasSnapshot || $hasLegacySnapshot) ? 'hidden' : '' }}">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    <p class="mt-3 text-sm text-zinc-500">{{ __('stats.heatmap_waiting_snapshot') }}</p>
                    <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-zinc-400">{{ __('stats.heatmap_waiting_snapshot_hint') }}</p>
                    <button type="button" onclick="window.open(this.dataset.url, '_blank')"
                            data-url="{{ $website->scheme }}://{{ $website->host }}{{ $heatmap->path }}"
                            class="mt-4 rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700">{{ __('stats.heatmap_generate_snapshot') }}</button>
                </div>
            </div>
            @if($hasLegacySnapshot)
            <div id="click-legacy-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50/80">
                <div class="text-center max-w-sm">
                    <svg class="mx-auto h-10 w-10 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <p class="mt-2 text-sm text-amber-600">{{ __('stats.heatmap_legacy_snapshot') }}</p>
                </div>
            </div>
            @endif
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
                                    <div id="scroll-no-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50 {{ ($hasSnapshot || $hasLegacySnapshot) ? 'hidden' : '' }}">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    <p class="mt-3 text-sm text-zinc-400">{{ __('stats.no_scroll_data') }}</p>
                </div>
            </div>
            @if($hasLegacySnapshot)
            <div id="scroll-legacy-snapshot" class="absolute inset-0 flex items-center justify-center bg-zinc-50/80">
                <div class="text-center max-w-sm">
                    <svg class="mx-auto h-10 w-10 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    <p class="mt-2 text-sm text-amber-600">{{ __('stats.heatmap_legacy_snapshot') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ asset('assets/pixel/rrweb-player.min.css') }}?v=20260912">
<script src="{{ asset('assets/pixel/rrweb-player.min.js') }}"></script>
<script id="json-clicks" type="application/json">@json($clicks)</script>
<script id="json-scrolls" type="application/json">@json($scrolls)</script>
@php
    // @json 指令参数须为简单变量：复杂表达式（含 "]), " 序列）会被 Blade 编译器错误截断产出损坏 PHP
    $mapMeta = [
        'snapshot_url' => route('stats.heatmaps.snapshot', [$website->website_id, $heatmap->heatmap_id]),
        'device' => $device,
        'no_data_text' => __('stats.no_click_data'),
        'no_scroll_data_text' => __('stats.no_scroll_data'),
    ];
@endphp
<script id="json-map-meta" type="application/json">@json($mapMeta)</script>
<script>
const clicksData = JSON.parse(document.getElementById('json-clicks').textContent);
const scrollsData = JSON.parse(document.getElementById('json-scrolls').textContent);
const mapMeta = JSON.parse(document.getElementById('json-map-meta').textContent);
const snapshotUrl = mapMeta.snapshot_url + '?device=' + mapMeta.device;
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

// 安全清洗：置空快照中所有 <script> 节点的内容与外链/内嵌文档。
// 背景：采集端会把访客页面 DOM（含访客浏览器/扩展注入的 inline script，
// 如 SafariAppExtensionPage/browsersApi 桥接脚本）完整序列化；若原样回放，
// 这些脚本会在同源 iframe 里重新编译执行（Safari 报 "Can't create duplicate
// variable" 之类 SyntaxError，每次播放/拖动进度重复触发，且存在脚本注入与
// 统计污染风险）。配合 rrweb 默认 sandbox="allow-same-origin"（查看端不开
// UNSAFE_replayCanvas，rrweb 便不会向 sandbox 追加 allow-scripts）形成双保险。
function sanitizeRrwebEvents(events) {
    function strip(node) {
        if (!node || typeof node !== 'object') return;
        const tag = String(node.tagName || '').toLowerCase();
        if (node.type === 2 && tag === 'script') {
            node.childNodes = [];
            if (node.attrs) { node.attrs.src = ''; node.attrs.srcdoc = ''; }
            return;
        }
        if (node.type === 2 && tag === 'iframe' && node.attrs) {
            node.attrs.srcdoc = ''; // 嵌套文档由子节点快照重建，防 srcdoc 内脚本
        }
        const kids = node.childNodes;
        if (Array.isArray(kids)) { for (let i = 0; i < kids.length; i++) strip(kids[i]); }
    }
    if (!Array.isArray(events)) return events;
    events.forEach(function (ev) {
        const d = ev && ev.data;
        if (!d) return;
        if (d.node) strip(d.node); // FullSnapshot 根节点
        if (Array.isArray(d.adds)) d.adds.forEach(function (a) { if (a && a.node) strip(a.node); }); // Mutation 增量
    });
    return events;
}

function initSnapshotReplayer(containerId, onReady) {
    fetch(snapshotUrl)
        .then(r => r.ok ? r.json() : [])
        .then(rawData => {
            // The snapshot API returns the stored data object.
            // Format: { events: [...], viewport: {...} } — extract events array
            // Fallback: raw event array (legacy)
            let snapshotData = null;
            if (rawData && Array.isArray(rawData.events)) {
                snapshotData = rawData.events;
            } else if (Array.isArray(rawData) && rawData.length > 0) {
                snapshotData = rawData;
            }

            if (!snapshotData || snapshotData.length === 0) {
                if (onReady) onReady(null);
                return;
            }

            // rrweb-player requires at minimum [Meta(type 4), FullSnapshot(type 2)].
            // Validate that we have both event types.
            var hasMeta = snapshotData.some(function(e) { return e.type === 4; });
            var hasFull = snapshotData.some(function(e) { return e.type === 2; });
            if (!hasMeta || !hasFull) {
                console.warn('Heatmap snapshot missing required events (meta=' + hasMeta + ', full=' + hasFull + ')');
                if (onReady) onReady(null);
                return;
            }

            // 回放前清洗快照中的脚本节点（见 sanitizeRrwebEvents 注释）
            sanitizeRrwebEvents(snapshotData);

            const container = document.getElementById(containerId);
            if (!container) { if (onReady) onReady(null); return; }
            const panel = container.parentElement; // 画布/遮罩以该 relative 容器为定位基准
            const playerRoot = document.createElement('div');
            playerRoot.style.width = '100%';
            container.appendChild(playerRoot);

            // 全页渲染：采集端坐标按整页归一化（pageX/scrollWidth、pageY/scrollHeight），
            // FullSnapshot 也序列化了折叠线以下的完整 DOM，因此回放页必须：
            // 1) 以快照 Meta 记录的访客真实视口宽度布局（保证响应式布局与坐标一致）；
            // 2) 把 iframe 拉伸到重建文档的真实总高（而非固定 600px 只显示首屏）；
            // 3) 按容器宽度整体等比缩放，使热图点位与页面内容严格对齐。
            const metaEvent = snapshotData.find(e => e.type === 4) || {};
            const pageWidth = Math.max(320, (metaEvent.data && metaEvent.data.width) || container.clientWidth || 1024);
            const initHeight = (metaEvent.data && metaEvent.data.height) || 600;
            let fullHeight = initHeight;
            let rendered = false;

            try {
                const replayer = new rrwebPlayer({
                    target: playerRoot,
                    props: {
                        events: snapshotData,
                        width: pageWidth,
                        height: initHeight,
                        autoPlay: true,
                        showController: false,
                        // 不要开启 UNSAFE_replayCanvas：rrweb 会因此给回放 iframe 的
                        // sandbox 追加 allow-scripts，导致快照中（含访客扩展注入）的
                        // 脚本重新编译执行；且采集端未开 recordCanvas，开启无任何收益。
                    },
                });

                const applyScale = () => {
                    if (!rendered) return;
                    const scale = container.clientWidth / pageWidth;
                    const scaled = Math.round(fullHeight * scale);
                    playerRoot.style.transformOrigin = '0 0';
                    playerRoot.style.transform = 'scale(' + scale + ')';
                    container.style.height = scaled + 'px';
                    container.style.minHeight = scaled + 'px';
                    if (panel && panel !== document.body) {
                        panel.style.minHeight = scaled + 'px';
                    }
                    if (onReady) onReady(replayer);
                };

                // 图像异步加载会使文档高度变化 → 多次测量直至稳定，并触发画布重绘
                const expandToFullPage = () => {
                    try {
                        const rp = replayer.getReplayer ? replayer.getReplayer() : replayer;
                        const iframe = rp && rp.iframe;
                        const doc = iframe && iframe.contentDocument;
                        if (!doc || !doc.documentElement) return;
                        const h = Math.max(
                            doc.documentElement.scrollHeight || 0,
                            (doc.body && doc.body.scrollHeight) || 0,
                            doc.documentElement.offsetHeight || 0
                        );
                        if (h < 50) return;
                        rendered = true;
                        fullHeight = h;
                        iframe.style.width = pageWidth + 'px';
                        iframe.style.height = h + 'px';
                        let el = iframe.parentElement;
                        while (el && el !== playerRoot) {
                            el.style.width = pageWidth + 'px';
                            el.style.height = h + 'px';
                            el = el.parentElement;
                        }
                        playerRoot.style.width = pageWidth + 'px';
                        playerRoot.style.height = h + 'px';
                        applyScale();
                    } catch (e) { /* ignore */ }
                };

                setTimeout(() => { try { replayer.pause(); } catch (e) {} expandToFullPage(); }, 500);
                setTimeout(expandToFullPage, 1500);
                setTimeout(expandToFullPage, 3000);
                window.addEventListener('resize', applyScale);
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
    if (!clicksData.length) { ctx.fillStyle = '#a1a1aa'; ctx.font = '14px sans-serif'; ctx.textAlign = 'center'; ctx.fillText(mapMeta.no_data_text, canvas.width / 2, canvas.height / 2); return; }
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
    if (!entries.length) { ctx.fillStyle = '#a1a1aa'; ctx.font = '14px sans-serif'; ctx.textAlign = 'center'; ctx.fillText(mapMeta.noScrollDataText, canvas.width / 2, canvas.height / 2); return; }
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
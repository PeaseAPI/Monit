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
                <dd class="mt-1 text-sm text-zinc-600">{!! stat_time($replay->datetime) !!}</dd>
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
                    <td class="px-6 py-3 text-zinc-500">{!! stat_time($event->date, 'H:i:s') !!}</td>
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
        <div class="px-6 py-4 border-b border-zinc-100">
            <h2 class="text-lg font-semibold text-zinc-800">{{ __('stats.replay_player') }}</h2>
        </div>
        <div id="replay-container" class="relative bg-zinc-100" style="min-height:480px" data-events-url="{{ route('stats.replays.events', [$website->website_id, $replay->replay_id]) }}">
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
<link rel="stylesheet" href="{{ asset('assets/pixel/rrweb-player.min.css') }}?v=20260912">
<script src="{{ asset('assets/pixel/rrweb-player.min.js') }}?v=20260912"></script>
<script>
// 安全清洗：置空快照中所有 <script> 节点的内容与外链/内嵌文档
// （与 heatmaps 页 sanitizeRrwebEvents 保持一致，防快照脚本在回放 iframe 中执行）
function sanitizeRrwebEvents(events) {
    function strip(node) {
        if (!node || typeof node !== 'object') return;
        const tag = String(node.tagName || '').toLowerCase();
        if (node.type === 2 && tag === 'script') {
            // Safari 对 sandbox（无 allow-scripts）文档中出现的 <script> 元素
            // 本身就会报 "Blocked script execution"——即使内容与 src 均已清空
            // （堆栈落在 rrweb-player setupDom 构建文档阶段）。因此不能保留
            // script 空壳：把节点变形为 <template>（inert：无脚本语义、无布局、
            // 按 id 保留节点使后续 mutation 引用不失效），重建文档零 script。
            node.tagName = 'template';
            node.childNodes = [];
            // rrweb 序列化节点的属性字段是 attributes（1.x schema 实测如此）；
            // 此前误读 attrs 导致 delete 空转——script 靠 template 变形兜底
            // 无害，但下方 iframe 分支因此整个失效。双字段兼容防旧数据。
            const attrs = node.attributes || node.attrs;
            if (attrs) {
                delete attrs.src;
                delete attrs.srcdoc;
            }
            return;
        }
        if (node.type === 2 && tag === 'iframe') {
            // 同理 delete：iframe 重建后若保留 src，浏览器会重新加载原始文
            // 档（同源=统计污染，跨域=无谓流量），内容由子节点快照重建
            const attrs = node.attributes || node.attrs;
            if (attrs) {
                delete attrs.srcdoc;
                delete attrs.src;
            }
        }
        const kids = node.childNodes;
        if (Array.isArray(kids)) { for (let i = 0; i < kids.length; i++) strip(kids[i]); }
    }
    if (!Array.isArray(events)) return events;
    events.forEach(function (ev) {
        const d = ev && ev.data;
        if (!d) return;
        if (d.node) strip(d.node);
        if (Array.isArray(d.adds)) d.adds.forEach(function (a) { if (a && a.node) strip(a.node); });
    });
    return events;
}
(function () {
    const eventsUrl = document.getElementById('replay-container').dataset.eventsUrl;
    const loading = document.getElementById('replay-loading');
    const empty = document.getElementById('replay-empty');
    const container = document.getElementById('replay-container');

    fetch(eventsUrl)
        .then(r => r.ok ? r.json() : [])
        .then(events => {
            // 回放前清洗快照中的脚本节点（含访客浏览器/扩展注入的 inline script），
            // 防止其在同源 iframe 中重新编译执行（Safari "Can't create duplicate
            // variable" 之类 SyntaxError 的根因之一），详见 sanitizeRrwebEvents 注释
            if (Array.isArray(events)) sanitizeRrwebEvents(events);
            loading.style.display = 'none';
            if (!events || events.length === 0) {
                empty.style.display = '';
                return;
            }
            // 单帧会话提示：无任何交互事件（type 3）说明访客落地即离开，回放
            // 只有一个静态快照（观感如同“一张截图”），显式提示避免被误认为
            // 播放器故障（如会话 149 仅 Meta+FullSnapshot 两个事件）
            if (!events.some(function (e) { return e && e.type === 3; })) {
                const hint = document.createElement('div');
                hint.className = 'mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800';
                hint.textContent = @js(__('stats.replay_static_hint'));
                container.parentElement.insertBefore(hint, container);
            }

            // 控制条统一使用 rrweb-player 自带底栏（进度/时间/倍速/播放暂停齐全），
            // 不再叠加自定义按钮——修复播放键与时间戳重叠、控制区拥挤问题
            const playerRoot = document.createElement('div');
            playerRoot.style.width = '100%';
            // 播放器居中：手机/竖屏录制的播放器宽度小于容器时，避免一侧露出死黑/空白区
            playerRoot.style.margin = '0 auto';
            container.appendChild(playerRoot);

            // 视口高度随容器宽度自适应（约 16:10，钳制 420-760px），替代固定 480px 裁剪
            let playerWidth = container.clientWidth;
            let playerHeight = Math.max(420, Math.min(760, Math.round(playerWidth * 0.62)));

            // 访客真实视口（rrweb Meta 事件 type=4 记录录制时的窗口宽高）：
            // 手机端按原视口尺寸渲染（如 375x812），不再放大到容器宽导致画面
            // 超长；页内滚动由回放事件驱动自动跟随访客滚动。桌面端视口宽于
            // 容器时按容器宽等比缩小；竖屏超高钳制 1000px 防异常数据。
            if (Array.isArray(events)) {
                for (let i = 0; i < events.length; i++) {
                    const ev = events[i];
                    if (ev && ev.type === 4 && ev.data && ev.data.width > 0 && ev.data.height > 0) {
                        let vw = ev.data.width, vh = ev.data.height;
                        // 脏 Meta 防御：竖屏手机视口高宽比物理上不会超过 ~2.4，超过说明
                        // Meta 高度是文档高度或被页面脚本篡改的值（会话 149 实测
                        // 375x4800，旧版采集脚本未改写 Meta），直接采用会把画面拉成
                        // 超高窄条——用户观感即“手机全屏截图、分辨率不正常”。按
                        // iPhone 视口比例（~2.17）回退，历史脏数据安全。
                        if (vh / vw > 2.6) vh = Math.round(vw * 2.17);
                        const scale = Math.min(container.clientWidth / vw, 1);
                        playerWidth = Math.round(vw * scale);
                        playerHeight = Math.min(1000, Math.round(vh * scale));
                        break;
                    }
                }
            }

            // 容器高度贴合播放器实际高度（含 rrweb-player 底部控制条约 56px），
            // 消除固定 min-height:480px 在小视口播放器下多出的深色/空白块
            container.style.minHeight = Math.max(240, Math.round(playerHeight + 56)) + 'px';

            try {
                new rrwebPlayer({
                    target: playerRoot,
                    props: {
                        events: events,
                        width: playerWidth,
                        height: playerHeight,
                        autoPlay: false,
                        showController: true,
                        // 不开启 UNSAFE_replayCanvas：rrweb 会因此给 sandbox 追加
                        // allow-scripts，令快照中脚本在回放 iframe 里执行；采集端
                        // 未开启 recordCanvas，开启无收益。
                    },
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
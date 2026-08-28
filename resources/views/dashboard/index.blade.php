@extends('layouts.app', ['nav' => 'dashboard'])

@section('title', '仪表盘')

@section('content')
    {{-- 顶部：网站选择 + 时间范围 --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold">{{ $website->name }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                {{ $website->host }}
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $website->isLightweight() ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                    {{ $website->isLightweight() ? '轻量模式' : '完整模式' }}
                </span>
                <span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-600">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    {{ $realtime }} 实时在线
                </span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (count($websites) > 1)
                <select onchange="window.location='{{ route('dashboard') }}?website_id='+this.value+'&range={{ $range }}'"
                        class="rounded-xl border-zinc-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    @foreach ($websites as $w)
                        <option value="{{ $w->website_id }}" {{ $w->website_id === $website->website_id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            @endif

            <div class="flex rounded-xl border border-zinc-200 bg-white p-1 shadow-sm">
                @foreach ([1 => '今日', 7 => '7 天', 30 => '30 天'] as $r => $label)
                    <a href="{{ route('dashboard', ['website_id' => $website->website_id, 'range' => $r]) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $range === $r ? 'bg-brand-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <a href="{{ route('dashboard.install', $website->website_id) }}"
               class="rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50">
                安装代码
            </a>
        </div>
    </div>

    {{-- 指标卡片 --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        @php
            $cards = [
                ['label' => '浏览量', 'value' => number_format($overview['pageviews']), 'hint' => '页面浏览总次数'],
                ['label' => '独立访客', 'value' => number_format($overview['visitors']), 'hint' => '去重访客数'],
                ['label' => '会话数', 'value' => number_format($overview['sessions']), 'hint' => '访问会话总数'],
                ['label' => '跳出率', 'value' => $overview['bounce_rate'].'%', 'hint' => '仅浏览单页的会话'],
                ['label' => '平均停留', 'value' => $overview['avg_duration'] > 0 ? gmdate('i:s', $overview['avg_duration']) : '-', 'hint' => '每次会话平均时长'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-zinc-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-zinc-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>
    {{-- 趋势图 --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">浏览量趋势</h3>
        <div class="mt-4 flex h-48 items-end gap-1.5">
            @php $maxPv = max(1, max(array_column($series, 'pageviews'))); @endphp
            @foreach ($series as $day)
                <div class="group relative flex h-full flex-1 items-end">
                    <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition group-hover:from-brand-700 group-hover:to-brand-500"
                         style="height: {{ max(2, (int) round($day['pageviews'] / $maxPv * 100)) }}%"></div>
                    <div class="pointer-events-none absolute -top-2 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white group-hover:block">
                        <p class="font-medium">{{ $day['date'] }}</p>
                        <p class="text-zinc-300">浏览 {{ $day['pageviews'] }} · 访客 {{ $day['visitors'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-xs text-zinc-400">
            <span>{{ $series[0]['date'] ?? '' }}</span>
            <span>{{ $series[count($series) - 1]['date'] ?? '' }}</span>
        </div>
    </div>

    {{-- 维度排行 --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @php
            $panels = [
                ['title' => '热门页面', 'items' => $topPaths],
                ['title' => '来源网站', 'items' => $topReferrers],
                ['title' => '国家 / 地区', 'items' => $topCountries],
                ['title' => '设备类型', 'items' => $topDevices],
            ];
        @endphp
        @foreach ($panels as $panel)
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-zinc-700">{{ $panel['title'] }}</h3>
                @if (empty($panel['items']))
                    <p class="mt-4 text-sm text-zinc-400">暂无数据</p>
                @else
                    @php $panelMax = max(1, max(array_column($panel['items'], 'count'))); @endphp
                    <ul class="mt-4 space-y-3">
                        @foreach ($panel['items'] as $item)
                            <li>
                                <div class="flex items-center justify-between gap-4 text-sm">
                                    <span class="truncate font-medium text-zinc-700">{{ $item['key'] }}</span>
                                    <span class="shrink-0 tabular-nums text-zinc-500">{{ number_format($item['count']) }}</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                                    <div class="h-full rounded-full bg-brand-500/70" style="width: {{ (int) round($item['count'] / $panelMax * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endsection

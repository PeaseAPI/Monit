@extends('layouts.app', ['nav' => 'dashboard'])

@section('title', __('dashboard.title'))

@section('content')
    {{-- Top: website selector + time range --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold">{{ $website->name }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                {{ $website->host }}
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $website->isLightweight() ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                    {{ $website->isLightweight() ? __('websites.lightweight_mode_label') : __('websites.advanced_mode_label') }}
                </span>
                <span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-600">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    {{ $realtime }} {{ __('dashboard.realtime_online') }}
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
                @foreach ([1 => __('dashboard.today'), 7 => __('dashboard.7days'), 30 => __('dashboard.30days')] as $r => $label)
                    <a href="{{ route('dashboard', ['website_id' => $website->website_id, 'range' => $r]) }}"
                       class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $range === $r ? 'bg-brand-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <a href="{{ route('dashboard.install', $website->website_id) }}"
               class="rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50">
                                {{ __('dashboard.install_code') }}
            </a>
        </div>
    </div>

    {{-- Metric cards --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        @php
            $cards = [
                ['label' => __('dashboard.pageviews'), 'value' => number_format($overview['pageviews']), 'hint' => __('dashboard.pageviews_hint'), 'grad' => 'from-brand-500 to-brand-700', 'icon' => 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ['label' => __('dashboard.unique_visitors'), 'value' => number_format($overview['visitors']), 'hint' => __('dashboard.visitors_hint'), 'grad' => 'from-sky-500 to-blue-600', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
                ['label' => __('dashboard.sessions'), 'value' => number_format($overview['sessions']), 'hint' => __('dashboard.sessions_hint'), 'grad' => 'from-violet-500 to-purple-600', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => __('dashboard.bounce_rate'), 'value' => $overview['bounce_rate'].'%', 'hint' => __('dashboard.bounce_rate_hint'), 'grad' => 'from-amber-500 to-orange-600', 'icon' => 'M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3'],
                ['label' => __('dashboard.avg_duration'), 'value' => $overview['avg_duration'] > 0 ? gmdate('i:s', $overview['avg_duration']) : '-', 'hint' => __('dashboard.avg_duration_hint'), 'grad' => 'from-emerald-500 to-teal-600', 'icon' => 'M12 6v6l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-zinc-900/5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium text-zinc-500">{{ $card['label'] }}</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $card['grad'] }} text-white shadow-md shadow-zinc-900/10 transition duration-300 group-hover:scale-110">
                        <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-zinc-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>
    {{-- Trend chart --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('dashboard.pageviews_trend') }}</h3>
        <div class="mt-4 flex h-48 items-end gap-1.5">
            @php $maxPv = max(1, max(array_column($series, 'pageviews'))); @endphp
            @foreach ($series as $day)
                <div class="group relative flex h-full flex-1 items-end">
                    <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition group-hover:from-brand-700 group-hover:to-brand-500"
                         style="height: {{ max(2, (int) round($day['pageviews'] / $maxPv * 100)) }}%"></div>
                    <div class="pointer-events-none absolute -top-2 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white group-hover:block">
                        <p class="font-medium">{{ $day['date'] }}</p>
                        <p class="text-zinc-300">{{ __('dashboard.pageviews') }} {{ $day['pageviews'] }} · {{ __('dashboard.visitors_short') }} {{ $day['visitors'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex justify-between text-xs text-zinc-400">
            <span>{{ $series[0]['date'] ?? '' }}</span>
            <span>{{ $series[count($series) - 1]['date'] ?? '' }}</span>
        </div>
    </div>

    {{-- Dimension rankings --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @php
            $panels = [
                ['title' => __('dashboard.top_pages'), 'items' => $topPaths],
                ['title' => __('dashboard.top_referrers'), 'items' => $topReferrers],
                ['title' => __('dashboard.top_countries'), 'items' => $topCountries],
                ['title' => __('dashboard.top_devices'), 'items' => $topDevices],
            ];
        @endphp
        @foreach ($panels as $panel)
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-zinc-700">{{ $panel['title'] }}</h3>
                @if (empty($panel['items']))
                    <p class="mt-4 text-sm text-zinc-400">{{ __('common.no_data') }}</p>
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

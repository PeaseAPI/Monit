@extends('layouts.app')
@section('title', __('stats.visitor_detail'))
@section('content')
@php
    $deviceLabel = ['desktop' => __('dashboard.device_desktop'), 'mobile' => __('dashboard.device_mobile'), 'tablet' => __('dashboard.device_tablet')][$profile['device_type'] ?? ''] ?? __('stats.unknown');
    $countryFlag = ! empty($profile['country_code']) ? \App\Support\CountryNames::flag($profile['country_code']) : null;
    $countryName = ! empty($profile['country_code']) ? \App\Support\CountryNames::name($profile['country_code'], app()->getLocale()) : null;
    // 事件类型 → 徽标样式（对标 51.LA 访问明细：浏览/进入/出站/转化分色）
    $typeMeta = [
        'landing_page' => ['label' => __('stats.event_type_landing_page'), 'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700'],
        'pageview' => ['label' => __('stats.event_type_pageview'), 'dot' => 'bg-brand-500', 'badge' => 'bg-brand-50 text-brand-700'],
        'outbound_click' => ['label' => __('stats.event_type_outbound_click'), 'dot' => 'bg-amber-500', 'badge' => 'bg-amber-50 text-amber-700'],
        'goal_conversion' => ['label' => __('stats.event_type_goal_conversion'), 'dot' => 'bg-fuchsia-500', 'badge' => 'bg-fuchsia-50 text-fuchsia-700'],
    ];
    // 停留时长格式化：<1m → "42s"，<1h → "3m 05s"，否则 "2h 15m"
    $dwell = fn (?int $gap): string => match (true) {
        $gap === null, $gap < 1 => '0s',
        $gap < 60 => $gap.'s',
        $gap < 3600 => intdiv($gap, 60).'m '.str_pad((string) ($gap % 60), 2, '0', STR_PAD_LEFT).'s',
        default => intdiv($gap, 3600).'h '.str_pad((string) (intdiv($gap, 60) % 60), 2, '0', STR_PAD_LEFT).'m',
    };
@endphp
<div class="max-w-6xl">
    <a href="{{ route('stats.visitors', $website) }}" class="mb-4 inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-500 shadow-sm transition hover:bg-zinc-50 hover:text-zinc-700">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
        {{ __('stats.visitor_list') }}
    </a>

    {{-- 头部信息卡（头像 + 画像摘要 + 概览指标条） --}}
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-4 p-6">
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-2xl shadow-md shadow-brand-600/20">
                {{ $countryFlag ?? '👤' }}
            </span>
            <div class="min-w-0">
                <h1 class="flex flex-wrap items-center gap-2 text-xl font-bold text-zinc-900">
                    {{ __('stats.visitor_detail') }}
                    <span class="rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-sm font-semibold text-zinc-500">{{ $profile['label'] }}</span>
                </h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-zinc-500">
                    <span>{{ $deviceLabel }}</span>
                    <span class="text-zinc-300">·</span>
                    <span>{{ $profile['os_name'] ?? '—' }} / {{ $profile['browser_name'] ?? '—' }}</span>
                    @if ($countryName)
                        <span class="text-zinc-300">·</span>
                        <span>{{ $countryFlag }} {{ $countryName }}</span>
                    @endif
                </p>
            </div>
            @if (($profile['total_replays'] ?? 0) > 0)
                <span class="ml-auto inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 9l5 3-5 3V9z"/></svg>
                    {{ __('stats.replays') }} × {{ $profile['total_replays'] }}
                </span>
            @endif
        </div>
        <div class="grid grid-cols-2 divide-x divide-zinc-100 border-t border-zinc-100 bg-zinc-50/60 sm:grid-cols-4">
            <div class="px-5 py-3">
                <p class="text-xs font-medium text-zinc-400">{{ __('stats.events') }}</p>
                <p class="mt-0.5 text-lg font-bold tabular-nums text-zinc-900">{{ number_format($profile['total_events']) }}</p>
            </div>
            <div class="px-5 py-3">
                <p class="text-xs font-medium text-zinc-400">{{ __('stats.visitor_sessions') }}</p>
                <p class="mt-0.5 text-lg font-bold tabular-nums text-zinc-900">{{ $profile['total_sessions'] !== null ? number_format($profile['total_sessions']) : '—' }}</p>
            </div>
            <div class="px-5 py-3">
                <p class="text-xs font-medium text-zinc-400">{{ __('stats.first_seen') }}</p>
                <p class="mt-0.5 text-sm font-semibold">{!! stat_time($profile['first_date'] ?? null, 'Y-m-d H:i', 'tabular-nums text-zinc-900') !!}</p>
            </div>
            <div class="px-5 py-3">
                <p class="text-xs font-medium text-zinc-400">{{ __('stats.last_seen') }}</p>
                <p class="mt-0.5 text-sm font-semibold">{!! stat_time($profile['last_date'] ?? null, 'Y-m-d H:i', 'tabular-nums text-zinc-900') !!}</p>
            </div>
        </div>
    </div>

    {{-- 访客画像：地理位置 / 设备环境 / 来源与进出页 --}}
    <div class="mt-4 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-700">
                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/></svg>
                {{ __('stats.geo_info') }}
            </h2>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.country') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ $countryFlag ? $countryFlag.' '.$countryName : __('stats.unknown') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.city') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ trim((string) ($profile['region_name'] ?? '').' '.(string) ($profile['city_name'] ?? '')) ?: __('stats.unknown') }}</dd>
                </div>
                @if (! empty($profile['ip']))
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-zinc-400">IP</dt>
                        <dd class="min-w-0 truncate text-right font-mono text-xs text-zinc-900">{{ $profile['ip'] }}</dd>
                    </div>
                @endif
            </dl>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-700">
                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 4V2m6 2V2M9 22v-2m6 2v-2M4 9H2m2 6H2m20-6h-2m2 6h-2"/></svg>
                {{ __('stats.device_info') }}
            </h2>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.device') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ $deviceLabel }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.os') }} / {{ __('stats.browser') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ ($profile['os_name'] ?? '—').' / '.($profile['browser_name'] ?? '—') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.screen_resolution') }}</dt>
                    <dd class="min-w-0 truncate text-right font-mono text-xs text-zinc-900">{{ $profile['screen_resolution'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.language') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ $profile['browser_language'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-700">
                <svg class="h-4 w-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                {{ __('stats.first_source') }}
            </h2>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.first_source') }}</dt>
                    <dd class="min-w-0 truncate text-right text-zinc-900">{{ $profile['first_referrer'] ?? __('stats.direct_visit') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.entry_page') }}</dt>
                    <dd class="min-w-0 truncate text-right font-mono text-xs text-zinc-900" title="{{ $profile['entry_path'] }}">{{ $profile['entry_path'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="shrink-0 text-zinc-400">{{ __('stats.exit_page') }}</dt>
                    <dd class="min-w-0 truncate text-right font-mono text-xs text-zinc-900" title="{{ $profile['exit_path'] }}">{{ $profile['exit_path'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- 会话分组时间线（对标 51.LA 访问明细：按会话分组 + 相邻事件停留时长 + 回放入口） --}}
    @foreach ($sessions as $session)
        <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 border-b border-zinc-100 bg-zinc-50/60 px-5 py-3">
                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-zinc-700">
                    <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                    {!! stat_time($session['date'] ?? null, 'Y-m-d H:i', 'text-zinc-700') !!}
                </span>
                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs tabular-nums text-zinc-500">{{ number_format($session['total_events']) }} {{ __('stats.events') }}</span>
                @if (($session['duration'] ?? 0) >= 1)
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs tabular-nums text-emerald-700">{{ __('stats.duration') }} {{ $dwell($session['duration']) }}</span>
                @endif
                @if (! empty($session['replay_id']))
                    <a href="{{ route('stats.replays.show', [$website->website_id, $session['replay_id']]) }}"
                       class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-500">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v14l11-7-11-7z"/></svg>
                        {{ __('stats.view_replay') }}
                    </a>
                @endif
            </div>
            <ol class="divide-y divide-zinc-50 px-5 py-2">
                @forelse ($session['events'] as $event)
                    @php
                        $meta = $typeMeta[$event['type']] ?? ['label' => $event['type'] ?: __('stats.unknown'), 'dot' => 'bg-zinc-400', 'badge' => 'bg-zinc-100 text-zinc-600'];
                    @endphp
                    <li class="flex items-center gap-3 py-2.5">
                        <span class="w-16 shrink-0 text-right font-mono text-xs tabular-nums text-zinc-400">{{ optional($event['date'])->format('H:i:s') ?? '—' }}</span>
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $meta['dot'] }}"></span>
                        <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                        <span class="min-w-0 flex-1 truncate font-mono text-xs text-zinc-700" title="{{ $event['path'] }}">{{ $event['path'] ?? '—' }}</span>
                        @if (! empty($event['referrer_host']))
                            <span class="hidden shrink-0 text-xs text-zinc-400 sm:inline">← {{ $event['referrer_host'] }}</span>
                        @endif
                        @if (($event['gap'] ?? 0) >= 1)
                            <span class="shrink-0 rounded bg-zinc-50 px-1.5 py-0.5 font-mono text-[11px] tabular-nums text-zinc-400">+{{ $dwell($event['gap']) }}</span>
                        @endif
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-zinc-400">{{ __('stats.no_data') }}</li>
                @endforelse
            </ol>
        </div>
    @endforeach

    {{-- 超出会话展示上限提示 --}}
    @if (($profile['total_sessions'] ?? 0) > count($sessions))
        <p class="mt-3 text-center text-xs text-zinc-400">{{ __('stats.visitor_sessions_capped', ['count' => count($sessions)]) }}</p>
    @endif
</div>
@endsection

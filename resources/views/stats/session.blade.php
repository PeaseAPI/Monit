@extends('layouts.app')
@section('title', __('stats.session_detail'))
@section('content')
<div class="max-w-5xl">
    <div class="mb-6">
        <a href="{{ route('stats.visitor', [$website, $session->visitor_id]) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('stats.session_detail') }} <span class="font-mono text-sm text-zinc-400">{{ $session->session_uuid }}</span></h1>
            <div class="flex items-center gap-4 text-sm text-zinc-500">
                <span>{{ __('stats.click_date') }}：<time class="font-medium text-zinc-700">{{ optional($session->date)->format('Y-m-d H:i') ?? '—' }}</time></span>
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">{{ $session->total_events }} {{ __('stats.events') }}</span>
            </div>
        </div>
    </div>

    {{-- 访客画像 --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-xs font-medium text-zinc-400">{{ __('stats.country') }}</p>
            <p class="mt-2 text-sm font-semibold text-zinc-900">
                @if ($session->visitor?->country_code)
                    {{ \App\Support\CountryNames::flag($session->visitor->country_code) }} {{ \App\Support\CountryNames::name($session->visitor->country_code, app()->getLocale()) }}
                @else
                    {{ __('stats.unknown') }}
                @endif
            </p>
            @if ($session->visitor?->city_name)
                <p class="mt-1 text-xs text-zinc-400">{{ $session->visitor->city_name }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-xs font-medium text-zinc-400">{{ __('stats.device_type') }}</p>
            <p class="mt-2 text-sm font-semibold text-zinc-900">{{ $session->visitor?->device_type ?: __('stats.unknown') }}</p>
            @if ($session->visitor?->screen_resolution)
                <p class="mt-1 text-xs text-zinc-400">{{ $session->visitor->screen_resolution }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-xs font-medium text-zinc-400">{{ __('stats.os') }}</p>
            <p class="mt-2 text-sm font-semibold text-zinc-900">{{ $session->visitor?->os_name ?: __('stats.unknown') }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-xs font-medium text-zinc-400">{{ __('stats.browser') }}</p>
            <p class="mt-2 text-sm font-semibold text-zinc-900">{{ $session->visitor?->browser_name ?: __('stats.unknown') }}</p>
            @if ($session->visitor?->browser_language)
                <p class="mt-1 text-xs text-zinc-400">{{ __('stats.language') }}: {{ $session->visitor->browser_language }}</p>
            @endif
        </div>
    </div>

    {{-- 进入 → 退出路径 --}}
    <div class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-5">
        <div class="flex items-center gap-2">
            <span class="rounded-lg bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-600">{{ __('stats.entry_page') }}</span>
            <span class="font-mono text-sm text-zinc-700">{{ $session->events->first()?->path ?? '—' }}</span>
        </div>
        <svg class="h-4 w-4 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
        <div class="flex items-center gap-2">
            <span class="rounded-lg bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-500">{{ __('stats.exit_page') }}</span>
            <span class="font-mono text-sm text-zinc-700">{{ $session->events->last()?->path ?? '—' }}</span>
        </div>
    </div>

    {{-- 事件时间线 --}}
    <div class="mt-4 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-sm font-semibold text-zinc-700">{{ __('stats.events_by_type') }}</h2></div>
        <ol class="relative px-6 py-4">
            @forelse ($session->events as $event)
                @php
                    $typeMeta = [
                        'landing_page' => ['label' => __('stats.event_type_landing_page'), 'class' => 'bg-emerald-100 text-emerald-700'],
                        'pageview' => ['label' => __('stats.event_type_pageview'), 'class' => 'bg-brand-100 text-brand-700'],
                        'outbound_click' => ['label' => __('stats.event_type_outbound_click'), 'class' => 'bg-amber-100 text-amber-700'],
                        'goal_conversion' => ['label' => __('stats.event_type_goal_conversion'), 'class' => 'bg-fuchsia-100 text-fuchsia-700'],
                    ][$event->type] ?? ['label' => $event->type, 'class' => 'bg-zinc-100 text-zinc-600'];
                @endphp
                <li class="relative flex items-center gap-4 pb-5 pl-6 last:pb-1">
                    @if (! $loop->last)
                        <span class="absolute left-[5px] top-4 h-full w-px bg-zinc-200"></span>
                    @endif
                    <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full {{ $event->type === 'pageview' ? 'bg-brand-500' : 'bg-emerald-500' }}"></span>
                    <span class="w-32 shrink-0 text-xs tabular-nums text-zinc-400">{{ optional($event->date)->format('m-d H:i:s') }}</span>
                    <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-medium {{ $typeMeta['class'] }}">{{ $typeMeta['label'] }}</span>
                    <span class="truncate font-mono text-sm text-zinc-700">{{ $event->path }}</span>
                    @if ($event->title)
                        <span class="ml-auto hidden shrink-0 text-xs text-zinc-400 sm:inline">{{ \Illuminate\Support\Str::limit($event->title, 24) }}</span>
                    @endif
                </li>
                @if ($event->utm_source || $event->utm_campaign || $event->referrer_host)
                    <li class="mb-4 ml-10 flex flex-wrap gap-2 text-xs text-zinc-400">
                        @if ($event->referrer_host)<span class="rounded bg-zinc-50 px-1.5 py-0.5">&larr; {{ $event->referrer_host }}{{ $event->referrer_path ? '/'.$event->referrer_path : '' }}</span>@endif
                        @if ($event->utm_source)<span class="rounded bg-zinc-50 px-1.5 py-0.5">utm_source: {{ $event->utm_source }}</span>@endif
                        @if ($event->utm_medium)<span class="rounded bg-zinc-50 px-1.5 py-0.5">utm_medium: {{ $event->utm_medium }}</span>@endif
                        @if ($event->utm_campaign)<span class="rounded bg-zinc-50 px-1.5 py-0.5">utm_campaign: {{ $event->utm_campaign }}</span>@endif
                    </li>
                @endif
            @empty
                <li class="py-6 text-center text-sm text-zinc-400">{{ __('stats.no_data') }}</li>
            @endforelse
        </ol>
    </div>
</div>
@endsection

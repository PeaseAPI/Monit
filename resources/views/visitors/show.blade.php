@extends('layouts.app')
@section('title', __('stats.visitor_detail'))
@section('content')
<div class="py-8">
    <div class="mb-6"><a href="{{ route('stats.visitors', $website) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.visitor_detail') }} #{{ $visitor->visitor_id }}</h1></div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="font-semibold text-zinc-900">{{ __('stats.device_info') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.device') }}</dt><dd class="text-zinc-900">{{ $visitor->device_type }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.os') }}</dt><dd class="text-zinc-900">{{ $visitor->os_name }} {{ $visitor->os_version }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.browser') }}</dt><dd class="text-zinc-900">{{ $visitor->browser_name }} {{ $visitor->browser_version }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.screen_resolution') }}</dt><dd class="text-zinc-900">{{ $visitor->screen_resolution }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.language') }}</dt><dd class="text-zinc-900">{{ $visitor->browser_language }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.theme') }}</dt><dd class="text-zinc-900">{{ $visitor->theme }}</dd></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="font-semibold text-zinc-900">{{ __('stats.geo_info') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.country') }}</dt><dd class="text-zinc-900">{{ $visitor->country_code }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.city') }}</dt><dd class="text-zinc-900">{{ $visitor->city_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('stats.continent') }}</dt><dd class="text-zinc-900">{{ $visitor->continent_code }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">IP</dt><dd class="text-zinc-900 font-mono text-xs">{{ $visitor->ip }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.visit_history') }}</h2></div>
        <div class="p-6">
            @forelse($sessions ?? [] as $session)
            <div class="flex items-center gap-4 py-3 border-b border-zinc-100 last:border-0">
                <span class="text-sm text-zinc-700">{{ $session->date }}</span>
                <span class="text-sm text-zinc-500">{{ $session->total_events }} {{ __('stats.events') }}</span>
            </div>
            @empty<p class="text-sm text-zinc-400">{{ __('common.no_data') }}</p>@endforelse
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('title', __('stats.replay'))
@section('content')
<div class="py-8">
    <div class="mb-6"><a href="{{ route('replays.index', $website) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.replay') }} #{{ $replay->replay_id }}</h1></div>

    <div class="grid gap-4 sm:grid-cols-4 mb-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.visitor') }}</div><div class="mt-1 text-lg font-semibold text-zinc-900">{{ $replay->visitor_id }}</div></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.duration') }}</div><div class="mt-1 text-lg font-semibold text-zinc-900">{{ $replay->duration ?? '-' }}</div></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.events') }}</div><div class="mt-1 text-lg font-semibold text-zinc-900">{{ $replay->total_events ?? '-' }}</div></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><div class="text-sm text-zinc-500">{{ __('stats.date') }}</div><div class="mt-1 text-lg font-semibold text-zinc-900">{{ $replay->datetime }}</div></div>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-900 mb-4">{{ __('stats.replay_player') }}</h2>
        <div id="replay-container" class="relative bg-zinc-100 rounded-xl overflow-hidden" style="min-height:400px">
            <div class="flex items-center justify-center h-96 text-zinc-400">
                <p>{{ __('stats.replay_loading') }}</p>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-4">
            <button id="replay-play" class="rounded-xl bg-brand-600 px-4 py-2 text-sm text-white hover:bg-brand-700">{{ __('stats.play') }}</button>
            <button id="replay-pause" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">{{ __('stats.pause') }}</button>
            <input type="range" id="replay-progress" min="0" max="100" value="0" class="flex-1">
            <span id="replay-time" class="text-sm text-zinc-500">0:00</span>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="mb-6"><a href="{{ route('replays.index', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_replays') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.replay_detail_title') }}</h1></div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6"><dl class="space-y-3"><div><dt class="text-sm font-medium text-zinc-500">{{ __('stats.replay_visitor_id') }}</dt><dd class="mt-1">{{ $replay->visitor_id }}</dd></div><div><dt class="text-sm font-medium text-zinc-500">{{ __('stats.replay_time') }}</dt><dd class="mt-1 text-zinc-600">{{ $replay->datetime }}</dd></div></dl></div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-8 text-center"><p class="text-zinc-500">{{ __('stats.replay_player_area') }}</p></div>
</div>
@endsection
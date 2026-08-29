@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="mb-6"><a href="{{ route('teams.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $team->name }}</h1></div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('teams.member_list') }}</h2>
        <div class="mt-4 space-y-2">
            @forelse($members ?? [] as $m)<div class="flex items-center gap-3 p-2"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium">{{ mb_substr($m->name ?? $m->email, 0, 1) }}</span><span>{{ $m->name ?? $m->email }}</span></div>
            @empty<p class="text-sm text-zinc-500">{{ __('teams.no_members') }}</p>@endforelse
        </div>
    </div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('teams.team_websites') }}</h2>
        <div class="mt-4 space-y-2">
            @forelse($userWebsites ?? [] as $w)<div class="flex items-center gap-3 p-2"><span class="text-sm text-zinc-700">{{ $w->name ?? $w->host }}</span></div>
            @empty<p class="text-sm text-zinc-500">{{ __('teams.no_linked_websites') }}</p>@endforelse
        </div>
    </div>
</div>
@endsection
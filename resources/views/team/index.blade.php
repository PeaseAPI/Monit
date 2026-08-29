@extends('layouts.app')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('teams.team_management') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('teams.team_management_desc') }}</p>
    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @forelse($teams ?? [] as $team)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <h3 class="text-base font-semibold text-zinc-900">{{ $team->name }}</h3>
            <p class="mt-1 text-xs text-zinc-500">{{ __('teams.created_at') }} {{ $team->datetime }}</p>
            <div class="mt-3 flex gap-2"><a href="{{ route('teams.show', $team->team_id) }}" class="text-sm text-brand-600 hover:underline">{{ __('teams.manage') }}</a></div>
        </div>
    @empty<p class="text-zinc-500">{{ __('teams.no_teams') }}</p>@endforelse
    </div>
</div>
@endsection
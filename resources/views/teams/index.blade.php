@extends('layouts.app')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('teams.my_teams') }}</h1>
    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @forelse($teams ?? [] as $team)
        <a href="{{ route('teams.show', $team->team_id) }}" class="rounded-2xl border border-zinc-200 bg-white p-5 hover:border-zinc-300">
            <h3 class="text-base font-semibold text-zinc-900">{{ $team->name }}</h3>
            <p class="mt-1 text-xs text-zinc-500">{{ __('teams.created_at') }} {{ $team->datetime }}</p>
        </a>
    @empty<p class="text-zinc-500">{{ __('teams.no_teams') }}</p>@endforelse
    </div>
    @if(!empty($invitations))
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('teams.pending_invitations') }}</h2>
        <div class="mt-4 space-y-3">
            @foreach($invitations as $inv)
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 flex items-center justify-between">
                <span class="text-sm text-zinc-700">{{ $inv->team->name ?? '-' }}</span>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('teams.accept', $inv->member_id) }}">@csrf @method('PUT')<button class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs text-white hover:bg-brand-700">{{ __('teams.accept') }}</button></form>
                    <form method="POST" action="{{ route('teams.remove', $inv->member_id) }}">@csrf @method('DELETE')<button class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs text-zinc-600 hover:bg-zinc-50">{{ __('teams.decline') }}</button></form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
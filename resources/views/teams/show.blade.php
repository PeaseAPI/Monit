@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6"><a href="{{ route('teams.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold text-zinc-900">{{ $team->name }}</h1>
            @if ($isOwner)
            <form method="POST" action="{{ route('teams.destroy', $team->team_id) }}" data-confirm="{{ __('teams.delete_confirm') }}">@csrf @method('DELETE')
                <button class="rounded-xl border border-red-200 px-4 py-2 text-sm text-red-500 hover:bg-red-50">{{ __('teams.delete_team') }}</button>
            </form>
            @endif
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('teams.member_list') }}</h2>
        <div class="mt-4 space-y-2">
            @forelse($members ?? [] as $m)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-100 p-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium">{{ mb_substr($m->user?->name ?? $m->user_email, 0, 1) }}</span>
                    <div>
                        <p class="text-sm font-medium text-zinc-900">{{ $m->user?->name ?? $m->user_email }}</p>
                        <p class="text-xs text-zinc-400">{{ $m->user_email }} · {{ $m->status === 1 ? __('teams.member_active') : __('teams.member_pending') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-zinc-500">
                    @if ($m->associations->isNotEmpty())
                        <span>{{ __('teams.granted_websites') }}: {{ $m->associations->map(fn ($a) => $a->website?->name ?? ('#'.$a->website_id))->implode('、') }}</span>
                    @endif
                    @if ($isOwner)
                    <form method="POST" action="{{ route('teams.remove', $m->team_member_id) }}" data-confirm="{{ __('teams.remove_confirm') }}">@csrf @method('DELETE')
                        <button class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs text-red-500 hover:bg-red-50">{{ __('teams.remove_member') }}</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty<p class="text-sm text-zinc-500">{{ __('teams.no_members') }}</p>@endforelse
        </div>
    </div>

    @if ($isOwner)
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('teams.invite_member') }}</h2>
        <p class="mt-1 text-xs text-zinc-400">{{ __('teams.invite_hint') }}</p>
        <form method="POST" action="{{ route('teams.invite') }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="team_id" value="{{ $team->team_id }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-zinc-500">{{ __('teams.invite_email') }}</label>
                    <input type="email" name="user_email" required class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                </div>
            </div>
            @if ($userWebsites->isNotEmpty())
            <div>
                <label class="mb-2 block text-xs text-zinc-500">{{ __('teams.grant_websites') }}</label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($userWebsites as $w)
                    <label class="flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" name="websites_ids[]" value="{{ $w->website_id }}">
                        {{ $w->name ?? $w->host }}
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('teams.send_invitation') }}</button>
        </form>
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold">{{ __('teams.team_websites') }}</h2>
        <div class="mt-4 space-y-2">
            @forelse($userWebsites ?? [] as $w)<div class="flex items-center gap-3 p-2"><span class="text-sm text-zinc-700">{{ $w->name ?? $w->host }}</span></div>
            @empty<p class="text-sm text-zinc-500">{{ __('teams.no_linked_websites') }}</p>@endforelse
        </div>
    </div>
    @endif
</div>
@endsection

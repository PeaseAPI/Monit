@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.goals_title')">
        <a href="{{ route('stats.goals.create', $website->website_id) }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('stats.create_goal') }}</a>
    </x-stats-header>
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_name') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_type') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_path') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_conversions') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_status') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($goals ?? [] as $g)<tr><td class="px-6 py-3">{{ $g->name }}</td><td class="px-6 py-3 text-zinc-500">{{ $g->type }}</td><td class="px-6 py-3 text-zinc-500">{{ $g->path ?? '-' }}</td><td class="px-6 py-3">{{ $g->conversions ?? 0 }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $g->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $g->is_enabled ? __('stats.goal_enabled') : __('stats.goal_disabled') }}</span></td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('stats.no_goals') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
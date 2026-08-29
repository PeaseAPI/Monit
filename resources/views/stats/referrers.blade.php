@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.referrers_title')" />
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.referrer_domain') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.referrer_visits') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.referrer_share') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($topReferrers ?? [] as $r)<tr><td class="px-6 py-3">{{ $r->domain ?? __('stats.direct_visit') }}</td><td class="px-6 py-3">{{ $r->count }}</td><td class="px-6 py-3 text-zinc-500">{{ $r->percentage ?? 0 }}%</td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('stats.no_data') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
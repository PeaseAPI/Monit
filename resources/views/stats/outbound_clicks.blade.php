@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.outbound_clicks_title')" />
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.click_url') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.click_count') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.click_date') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($clicks ?? [] as $c)<tr><td class="px-6 py-3">{{ $c->url }}</td><td class="px-6 py-3">{{ $c->count }}</td><td class="px-6 py-3 text-zinc-500">{{ $c->date }}</td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('stats.no_data') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
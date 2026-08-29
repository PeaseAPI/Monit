@extends('layouts.app')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('domains.title') }}</h1>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('domains.domain') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($domains ?? [] as $d)<tr><td class="px-6 py-3">{{ $d->name }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $d->is_verified ? 'bg-emerald-50 text-emerald-700' : 'bg-yellow-50 text-yellow-700' }}">{{ $d->is_verified ? __('common.verified') : __('common.pending') }}</span></td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="2">{{ __('common.no_domains') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
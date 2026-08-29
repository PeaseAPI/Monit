@extends('layouts.admin')
@section('title', __('admin.redeemed_codes'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.redeemed_codes') }}</h1></div>
    <a href="{{ route('admin.codes.index') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('admin.redeem_codes') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.code_code') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($redeemed as $r)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $r->user?->email ?? $r->user_id }}</td>
            <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $r->code?->code ?? '-' }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $r->datetime?->format('Y-m-d H:i') }}</td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $redeemed->links() }}
@endsection
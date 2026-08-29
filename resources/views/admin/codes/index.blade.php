@extends('layouts.admin')
@section('title', __('admin.redeem_codes'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.redeem_codes') }}</h1></div>
    <div class="flex gap-2">
        <a href="{{ route('admin.codes.redeemed') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('admin.redeemed_codes') }}</a>
        <a href="{{ route('admin.codes.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
    </div>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.code_name') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.code_code') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.code_type') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.code_redemptions') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($codes as $c)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $c->name }}</td>
            <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $c->code }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $c->type === 'plan' ? __('admin.code_type_plan') . ' (' . $c->plan_id . ' / ' . $c->days . __('admin.code_days') . ')' : $c->discount . '%' }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $c->redeemed_codes_count }}{{ $c->max_redemptions ? ' / ' . $c->max_redemptions : '' }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $c->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $c->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <a href="{{ route('admin.codes.edit', $c->code_id) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('admin.codes.destroy', $c->code_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $codes->links() }}
@endsection
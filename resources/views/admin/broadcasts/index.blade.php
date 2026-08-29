@extends('layouts.admin')
@section('title', __('admin.broadcasts'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.broadcasts') }}</h1></div>
    <a href="{{ route('admin.broadcasts.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.broadcast_target') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($broadcasts as $b)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $b->title }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ __('admin.broadcast_target_' . $b->target) }}{{ $b->target === 'plan' && $b->target_plan_id ? ' (' . $b->target_plan_id . ')' : '' }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ match($b->status) { 'sent' => 'bg-emerald-50 text-emerald-700', 'pending' => 'bg-amber-50 text-amber-700', 'processing' => 'bg-blue-50 text-blue-700', default => 'bg-zinc-100 text-zinc-500' } }}">{{ __('admin.broadcast_status_' . $b->status) }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $b->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                @if($b->status === 'draft')
                <form method="POST" action="{{ route('admin.broadcasts.send', $b->broadcast_id) }}" class="inline">@csrf @method('PUT')<button class="mr-3 text-sm text-emerald-600 hover:text-emerald-700">{{ __('admin.broadcast_send') }}</button></form>
                <a href="{{ route('admin.broadcasts.edit', $b->broadcast_id) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                @endif
                <form method="POST" action="{{ route('admin.broadcasts.destroy', $b->broadcast_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $broadcasts->links() }}
@endsection
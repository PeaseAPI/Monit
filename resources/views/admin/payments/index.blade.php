@extends('layouts.admin')
@section('title', __('admin.payment_list'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.payment_list') }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.payment_id') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.plan_price_col') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user_status') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($payments ?? [] as $p)<tr><td class="px-6 py-3">{{ $p->payment_id }}</td><td class="px-6 py-3">¥{{ number_format($p->total_amount ?? 0, 2) }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $p->status ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $p->status ? __('common.enabled') : __('common.disabled') }}</span></td></tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
@endsection
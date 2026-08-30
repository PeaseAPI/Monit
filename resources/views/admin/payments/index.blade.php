@extends('layouts.admin')
@section('title', __('admin.payment_list'))
@section('content')
<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.payment_list') }}</h1></div>
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('common.search') }}" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <button class="rounded-xl bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-700">{{ __('common.search') }}</button>
    </form>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.payment_id') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.processor') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.type') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.frequency') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.amount') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user_status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($payments ?? [] as $p)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $p->payment_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $p->email }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $p->payment_processor }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $p->type }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $p->frequency ?: '-' }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ number_format((float) $p->total_amount, 2) }} {{ $p->currency }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $p->status ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $p->status ? __('admin.paid') : __('admin.unpaid') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $p->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <a href="{{ route('admin.payments.invoice', $p->payment_id) }}" target="_blank" class="mr-3 text-sm text-brand-600 hover:text-brand-700">{{ __('admin.invoice') }}</a>
                <a href="{{ route('admin.payments.credit_note', $p->payment_id) }}" target="_blank" class="text-sm text-red-500 hover:text-red-700">{{ __('admin.credit_note') }}</a>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="9">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ ($payments ?? null)?->links() }}
@endsection

@extends('layouts.app')
@section('title', __('payments.wechat_pay_title'))

@section('content')
<div class="p-8 max-w-xl">
    <div class="rounded-2xl border border-zinc-200 bg-white p-8 text-center">
        <h1 class="text-xl font-bold text-zinc-900">{{ __('payments.wechat_pay_title') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">{{ __('payments.wechat_pay_desc') }}</p>

        <div class="mt-6 rounded-xl border border-dashed border-emerald-300 bg-emerald-50 p-6">
            <div class="text-xs font-medium uppercase tracking-wider text-emerald-600">{{ __('payments.wechat_code_url') }}</div>
            <code class="mt-2 block break-all rounded-lg bg-white px-4 py-3 text-sm text-zinc-800">{{ $result['code_url'] ?? '' }}</code>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-left text-sm">
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.wechat_out_trade_no') }}</div>
                <div class="mt-1 font-mono text-zinc-800">{{ $result['out_trade_no'] ?? '-' }}</div>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.amount') }}</div>
                <div class="mt-1 font-semibold text-zinc-800">{{ $payment->total_amount }} {{ $payment->currency }}</div>
            </div>
        </div>

        <p class="mt-6 text-xs text-zinc-400">{{ __('payments.wechat_pay_note') }}</p>
        <a href="{{ route('payments.history') }}" class="mt-4 inline-block text-sm text-brand-600 hover:underline">{{ __('payments.view_all') }}</a>
    </div>
</div>
@endsection

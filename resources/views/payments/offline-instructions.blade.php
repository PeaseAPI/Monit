@extends('layouts.app')
@section('title', __('payment.offline_instructions_title'))

@section('content')
<div class="max-w-2xl">
    <div class="rounded-2xl border border-zinc-200 bg-white p-8">
        <h1 class="text-xl font-bold text-zinc-900">{{ __('payment.offline_instructions_title') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">{{ __('payment.offline_instructions_desc') }}</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.payment_method') }}</div>
                <div class="mt-1 font-semibold text-zinc-800">{{ __('payments.processor_offline') }}</div>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.amount') }}</div>
                <div class="mt-1 font-semibold text-zinc-800">{{ $payment->total_amount }} {{ $payment->currency }}</div>
            </div>
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.order_no') }}</div>
                <div class="mt-1 font-mono text-zinc-800">#{{ $payment->payment_id }}</div>
            </div>
        </div>

        <h3 class="mt-8 text-sm font-semibold text-zinc-900">{{ __('payment.offline_instructions_default') }}</h3>
        <div class="mt-3 whitespace-pre-wrap rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">{{ $instructions }}</div>

        <h3 class="mt-8 text-sm font-semibold text-zinc-900">{{ __('payment.proof_upload_title') }}</h3>
        <form method="POST" action="{{ route('payments.proof', $payment->payment_id) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <input type="file" name="proof" required accept=".jpg,.jpeg,.png,.pdf" class="text-sm">
            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('payment.proof_upload_submit') }}</button>
        </form>

        <a href="{{ route('payments.history') }}" class="mt-6 inline-block text-sm text-brand-600 hover:underline">{{ __('payments.view_all') }}</a>
    </div>
</div>
@endsection

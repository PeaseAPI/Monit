@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-lg py-12">
    <h1 class="text-2xl font-bold">{{ __('payment.razorpay_checkout') }}</h1>
    <p class="mt-2 text-zinc-600">{{ __('payment.razorpay_processing') }}</p>

    <div class="mt-6 rounded-xl border p-6">
        <p><strong>{{ __('payment.amount') }}:</strong> {{ $payment->total_amount }} {{ $payment->currency }}</p>
        <p><strong>{{ __('payment.plan') }}:</strong> {{ $payment->frequency }}</p>
    </div>

    <form action="https://checkout.razorpay.com/v1/checkout.php" method="POST" class="mt-6">
        <input type="hidden" name="key" value="{{ $result['key_id'] }}">
        <input type="hidden" name="amount" value="{{ $result['amount'] }}">
        <input type="hidden" name="currency" value="{{ $result['currency'] }}">
        <input type="hidden" name="name" value="{{ config('app.name') }}">
        <input type="hidden" name="description" value="{{ $payment->frequency }}">
        <input type="hidden" name="order_id" value="{{ $result['order_id'] }}">
        <input type="hidden" name="prefill[name]" value="{{ $result['prefill']['name'] }}">
        <input type="hidden" name="prefill[email]" value="{{ $result['prefill']['email'] }}">
        <button type="submit" class="w-full rounded-xl bg-brand-600 px-4 py-3 text-white hover:bg-brand-700">
            {{ __('payment.pay_with_razorpay') }}
        </button>
    </form>
</div>
@endsection

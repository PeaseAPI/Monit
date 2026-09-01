@extends('layouts.app')
@section('title', __('payments.redeem_code'))

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('payments.redeem_code') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('payments.redeem_code_desc') }}</p>

    <form method="POST" action="{{ route('payments.redeem.submit') }}" class="mt-6 space-y-4">@csrf
        <div>
            <label class="block text-sm font-medium text-zinc-700">{{ __('payments.discount_code') }}</label>
            <input type="text" name="code" required placeholder="{{ __('payments.discount_code_placeholder') }}" class="form-input">
        </div>
        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('payments.redeem_code_btn') }}</button>
    </form>

    <a href="{{ route('payments.index') }}" class="mt-4 inline-block text-sm text-brand-600 hover:underline">{{ __('payments.back_to_payments') }}</a>
</div>
@endsection
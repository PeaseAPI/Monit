@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('pay.billing_title') }}</h1>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('pay.subscription_info') }}</h3>

        @if($user->payment_subscription_id)
        <div class="mt-3 space-y-2 text-sm text-zinc-600">
            <p>{{ __('pay.processor') }}: <span class="font-medium text-zinc-900">{{ $user->payment_processor }}</span></p>
            <p>{{ __('pay.subscription_id') }}: <code class="rounded bg-zinc-100 px-2 py-0.5 text-xs">{{ $user->payment_subscription_id }}</code></p>
            <p>{{ __('pay.total_paid') }}: <span class="font-medium text-zinc-900">{{ $user->payment_total_amount }} {{ $user->payment_currency }}</span></p>
        </div>

        <form method="POST" action="{{ route('pay.billing.cancel') }}" class="mt-4">
            @csrf
            <button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700"
                    onclick="return confirm('{{ __('pay.confirm_cancel') }}')">
                {{ __('pay.cancel_subscription') }}
            </button>
        </form>
        @else
        <p class="mt-3 text-sm text-zinc-500">{{ __('pay.no_active_subscription') }}</p>
        <a href="{{ route('payments.index') }}" class="mt-3 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
            {{ __('pay.choose_plan') }}
        </a>
        @endif
    </div>
</div>
@endsection
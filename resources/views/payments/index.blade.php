@extends('layouts.app')
@section('title', __('payments.title'))

@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('payments.title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('payments.subtitle') }}</p>

    {{-- 当前套餐 --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('payments.current_plan') }}</h3>
        <div class="mt-3 flex items-center gap-4">
            @php
                $planCurrency = $user->payment_currency ?? config('monit.payment.default_currency');
            @endphp
            <span class="rounded-xl bg-brand-100 px-4 py-2 text-sm font-semibold text-brand-700">{{ $currentPlan->name ?? __('payments.free_plan') }}</span>
            @if($currentPlan && ($currentPlan->prices[$planCurrency]['monthly'] ?? 0) > 0)
            <span class="text-sm text-zinc-500">{{ $currentPlan->prices[$planCurrency]['monthly'] }} {{ $planCurrency }} / {{ __('payments.monthly') }}</span>
            @endif
        </div>
        @if($user->plan_expiration_date)
        <p class="mt-2 text-xs text-zinc-400">{{ __('payments.expiration_date') }}：{{ $user->plan_expiration_date->format('Y-m-d') }}</p>
        @endif
    </div>

    {{-- 升级套餐 --}}
    @if($plans->count() > 0)
    <div class="mt-6">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('payments.choose_plan') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($plans as $plan)
            @php
                $currency = $user->payment_currency ?? config('monit.payment.default_currency');
                $prices = $plan->prices[$currency] ?? [];
            @endphp
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 flex flex-col">
                <h4 class="font-semibold text-zinc-900">{{ $plan->name }}</h4>
                <p class="mt-1 text-2xl font-bold text-brand-600">
                    {{ $prices['monthly'] ?? '—' }}
                    <span class="text-sm font-normal text-zinc-400">{{ $currency }} / {{ __('payments.monthly') }}</span>
                </p>
                @if($plan->description)
                <p class="mt-2 text-sm text-zinc-500">{{ $plan->description }}</p>
                @endif
                <div class="mt-auto pt-4">
                    <form method="POST" action="{{ route('payments.checkout') }}">@csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->plan_id }}">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-500">{{ __('payments.billing_frequency') }}</label>
                                <select name="frequency" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                                    @foreach(['monthly', 'annual', 'lifetime'] as $frequency)
                                    <option value="{{ $frequency }}">{{ __('payments.frequency_' . $frequency) }}（{{ $prices[$frequency] ?? '—' }} {{ $currency }}）</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500">{{ __('payments.payment_method') }}</label>
                                <select name="processor" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                                    @foreach(config('monit.payment.supported_processors') as $processor)
                                    <option value="{{ $processor }}">{{ __('payments.processor_' . $processor) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-500">{{ __('payments.discount_code') }}</label>
                                <input type="text" name="code" placeholder="{{ __('payments.discount_code_placeholder') }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                            </div>
                            <button class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('payments.subscribe') }}</button>
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        </div>
    @endif

    {{-- 兑换码 --}}
    <div class="mt-8 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('payments.redeem_code') }}</h3>
        <p class="mt-1 text-sm text-zinc-500">{{ __('payments.redeem_code_desc') }}</p>
                <a href="{{ route('payments.redeem') }}" class="mt-3 inline-block rounded-xl bg-zinc-100 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-200">{{ __('payments.redeem_code_btn') }}</a>
    </div>

    {{-- 支付历史 --}}
    <div class="mt-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('payments.history_title') }}</h2>
            <a href="{{ route('payments.history') }}" class="text-sm text-brand-600 hover:underline">{{ __('payments.view_all') }}</a>
        </div>
        <div class="mt-4 space-y-3">
            @forelse($recentPayments ?? [] as $payment)
            @php
                $statusKey = [0 => 'pending', 1 => 'completed', 2 => 'failed', 3 => 'refunded'][$payment->status] ?? 'pending';
            @endphp
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-900">{{ __('payments.processor_' . $payment->payment_processor) }}</p>
                    <p class="mt-1 text-xs text-zinc-400">{{ $payment->datetime->format('Y-m-d H:i') }} · {{ $payment->frequency }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-zinc-900">{{ $payment->total_amount }} {{ $payment->currency }}</p>
                    <span class="mt-1 inline-block rounded-lg px-2 py-0.5 text-xs font-medium {{ $payment->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ __('payments.status_' . $statusKey) }}</span>
                </div>
            </div>
            @empty
            <p class="text-sm text-zinc-400">{{ __('payments.no_payments') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
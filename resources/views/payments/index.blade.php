@extends('layouts.app')
@section('title', __('payments.title'))

@section('content')
<div class="max-w-5xl">
    {{-- Hero：当前套餐状态 --}}
    <div class="overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-indigo-800 p-8 text-white shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-6">
            <div>
                <p class="text-sm font-medium text-white/70">{{ __('payments.current_plan') }}</p>
                <div class="mt-2 flex flex-wrap items-baseline gap-3">
                    <h1 class="text-3xl font-bold">{{ $currentPlan->name ?? __('payments.free_plan') }}</h1>
                    @php
                        $heroCurrency = \App\Support\Currency::normalize($user->payment_currency ?? '');
                        $heroMonthly = $currentPlan ? \App\Support\Currency::planPrice($currentPlan, $heroCurrency, 'monthly') : null;
                        // 默认计费周期由后台 payment.default_payment_frequency 控制（控制器已传入）
                        $defaultFrequency = $defaultFrequency ?? 'monthly';
                    @endphp
                    @if($heroMonthly !== null && $heroMonthly > 0)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white/90 backdrop-blur">
                            {{ \App\Support\Currency::format($heroMonthly, $heroCurrency) }} {{ $heroCurrency }} / {{ __('payments.monthly') }}
                        </span>
                    @endif
                </div>
                @if($user->plan_expiration_date)
                    <p class="mt-2 text-xs text-white/60">{{ __('payments.expiration_date') }}：{{ $user->plan_expiration_date->format('Y-m-d') }}</p>
                @endif
            </div>
            <a href="{{ route('payments.redeem') }}"
               class="rounded-2xl border border-white/25 bg-white/10 px-5 py-3 text-sm font-medium text-white backdrop-blur transition hover:bg-white/20">
                🎟️ {{ __('payments.redeem_code') }}
            </a>
        </div>
    </div>

    {{-- 升级套餐 --}}
    @if($plans->count() > 0)
    <div class="mt-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-zinc-900">{{ __('payments.choose_plan') }}</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ __('payments.subtitle') }}</p>
            </div>

            {{-- 计费周期分段切换（联动所有卡片价格与表单 hidden frequency）
                 默认选中周期由后台 payment.default_payment_frequency 控制（取值见上方 PHP 块） --}}
            <div id="freq-switch" class="flex rounded-2xl border border-zinc-200 bg-white p-1 shadow-sm">
                @foreach(['monthly', 'annual', 'lifetime'] as $fi => $frequency)
                    <button type="button" data-freq="{{ $frequency }}"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition {{ $frequency === $defaultFrequency ? 'bg-brand-600 text-white shadow' : 'text-zinc-600 hover:bg-zinc-100' }}">
                        {{ __('payments.frequency_' . $frequency) }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="mt-6 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($plans as $plan)
                @php
                    $currency = \App\Support\Currency::normalize($user->payment_currency ?? '');
                    $prices = [
                        'monthly' => \App\Support\Currency::planPrice($plan, $currency, 'monthly'),
                        'annual' => \App\Support\Currency::planPrice($plan, $currency, 'annual'),
                        'lifetime' => \App\Support\Currency::planPrice($plan, $currency, 'lifetime'),
                    ];
                    // 年付相对 12 个月付的节省比例（两档价格齐全且年付更划算才显示）
                    $savePercent = null;
                    if ($prices['monthly'] !== null && $prices['monthly'] > 0 && $prices['annual'] !== null && $prices['annual'] > 0) {
                        $savePercent = (int) round((1 - $prices['annual'] / ($prices['monthly'] * 12)) * 100);
                        if ($savePercent <= 0) { $savePercent = null; }
                    }
                    $isPopular = $plans->count() > 1 && $loop->index === (int) floor($plans->count() / 2);
                @endphp
                <div class="relative flex flex-col rounded-3xl border {{ $isPopular ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-zinc-200' }} bg-white p-7 shadow-sm transition hover:shadow-md">
                    @if($isPopular)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-gradient-to-r from-brand-600 to-indigo-600 px-3 py-1 text-xs font-semibold text-white shadow">
                            🔥 {{ __('payments.popular') }}
                        </span>
                    @endif

                    <h4 class="text-lg font-bold text-zinc-900">{{ $plan->name }}</h4>

                    <div class="mt-4 flex items-baseline gap-1.5">
                        <span class="text-sm font-medium text-zinc-400">{{ \App\Support\Currency::symbol($currency) }}</span>
                        <span class="text-4xl font-extrabold tracking-tight text-zinc-900 tabular-nums plan-price"
                              data-prices='@json([array_map(fn($p) => $p !== null ? number_format($p, 2) : null, $prices)])'></span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-zinc-400 plan-suffix"
                       data-suffix-monthly="/ {{ __('payments.monthly') }}" data-suffix-annual="/ {{ __('payments.frequency_annual') }}" data-suffix-lifetime="{{ __('payments.price_suffix_lifetime') }}"></p>
                    <p class="mt-1 text-xs font-medium text-emerald-600 plan-save hidden" data-save="{{ $savePercent }}"></p>

                    @if($plan->description)
                        <p class="mt-4 text-sm leading-relaxed text-zinc-500">{{ $plan->description }}</p>
                    @endif

                    <div class="mt-auto pt-6">
                        <form method="POST" action="{{ route('payments.checkout') }}">@csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->plan_id }}">
                            <input type="hidden" name="frequency" value="monthly">
                            <label class="block text-xs font-medium text-zinc-500">{{ __('payments.payment_method') }}</label>
                            <select name="processor" class="form-input mt-1.5">
                                @foreach(config('monit.payment.supported_processors') as $processor)
                                    <option value="{{ $processor }}">{{ __('payments.processor_' . $processor) }}</option>
                                @endforeach
                            </select>
                            <label class="mt-4 block text-xs font-medium text-zinc-500">{{ __('payments.discount_code') }}</label>
                            <input type="text" name="code" placeholder="{{ __('payments.discount_code_placeholder') }}" class="form-input mt-1.5">
                            <button class="mt-5 w-full rounded-xl {{ $isPopular ? 'bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700' : 'bg-brand-600 hover:bg-brand-700' }} px-4 py-3 text-sm font-semibold text-white shadow transition">
                                {{ __('payments.subscribe') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
    {{-- 支付历史 --}}
    <div class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-zinc-900">{{ __('payments.history_title') }}</h2>
            <a href="{{ route('payments.history') }}" class="text-sm font-medium text-brand-600 hover:underline">{{ __('payments.view_all') }} →</a>
        </div>
        <div class="mt-4 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            @forelse($recentPayments ?? [] as $payment)
                @php
                    $statusKey = [0 => 'pending', 1 => 'completed', 2 => 'failed', 3 => 'refunded'][$payment->status] ?? 'pending';
                @endphp
                <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-6 py-4 last:border-0">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $payment->status === 1 ? 'bg-emerald-50 text-emerald-500' : 'bg-zinc-100 text-zinc-400' }}">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">{{ __('payments.processor_' . $payment->payment_processor) }}</p>
                            <p class="mt-0.5 text-xs text-zinc-400">{{ $payment->datetime->format('Y-m-d H:i') }} · {{ __('payments.frequency_' . $payment->frequency) }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-zinc-900 tabular-nums">{{ $payment->total_amount }} {{ $payment->currency }}</p>
                        <span class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $payment->status === 1 ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ __('payments.status_' . $statusKey) }}</span>
                    </div>
                </div>
            @empty
                <p class="px-6 py-8 text-center text-sm text-zinc-400">{{ __('payments.no_payments') }}</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    (function () {
        var saveText = {{ json_encode(__('payments.save_percent', ['percent' => ':percent'])) }};
        var prices = document.querySelectorAll('.plan-price');
        var suffixes = document.querySelectorAll('.plan-suffix');
        var saves = document.querySelectorAll('.plan-save');
        var hiddenFreq = document.querySelectorAll('input[name="frequency"]');

        function render(freq) {
            prices.forEach(function (el) {
                var data = JSON.parse(el.dataset.prices)[0];
                el.textContent = (data[freq] !== null && data[freq] !== undefined) ? data[freq] : '—';
            });
            suffixes.forEach(function (el) {
                el.textContent = el.dataset['suffix' + freq.charAt(0).toUpperCase() + freq.slice(1)] || '';
            });
            saves.forEach(function (el) {
                var pct = parseInt(el.dataset.save, 10);
                if (freq === 'annual' && pct > 0) {
                    el.textContent = saveText.replace(':percent', pct);
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
            hiddenFreq.forEach(function (input) { input.value = freq; });
        }

        document.querySelectorAll('#freq-switch [data-freq]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('#freq-switch [data-freq]').forEach(function (b) {
                    b.className = 'rounded-xl px-4 py-2 text-sm font-medium transition text-zinc-600 hover:bg-zinc-100';
                });
                btn.className = 'rounded-xl px-4 py-2 text-sm font-medium transition bg-brand-600 text-white shadow';
                render(btn.dataset.freq);
            });
        });

        render('monthly');
    })();
</script>
@endsection



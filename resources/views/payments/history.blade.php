@extends('layouts.app')
@section('title', __('payments.history_title'))

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('payments.history_title') }}</h1>
        <a href="{{ route('payments.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('payments.back_to_payments') }}</a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse($payments as $payment)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-zinc-900">{{ $payment->plan->name ?? '-' }}</p>
                <p class="mt-1 text-xs text-zinc-400">{{ $payment->datetime->format('Y-m-d H:i') }}</p>
                                <p class="mt-1 text-xs text-zinc-400">{{ __('payments.payment_method') }}：{{ __('payments.processor_' . $payment->payment_processor) }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-zinc-900">{{ number_format((float) $payment->total_amount, 2) }} {{ $payment->currency }}</p>
                @if($payment->status)
                    <span class="mt-1 inline-block rounded-lg px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700">{{ __('payments.status_completed') }}</span>
                @else
                    <span class="mt-1 inline-block rounded-lg px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700">{{ __('payments.status_pending') }}</span>
                    {{-- 待处理订单：继续支付（按原处理器重新发起，不新建订单） --}}
                    <a href="{{ route('payments.resume', $payment) }}"
                       class="mt-2 block rounded-xl bg-brand-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700">
                        {{ __('payments.resume_action') }}
                    </a>
                @endif
            </div>
        </div>
        @empty
        <p class="text-sm text-zinc-400">{{ __('payments.no_payments') }}</p>
        @endforelse
    </div>

    {{ $payments->links() }}
</div>
@endsection
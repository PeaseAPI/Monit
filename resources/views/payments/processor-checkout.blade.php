@extends('layouts.app')
@section('title', __('payments.checkout_title'))

@section('content')
<div class="p-8 max-w-2xl">
    <div class="rounded-2xl border border-zinc-200 bg-white p-8">
        <h1 class="text-xl font-bold text-zinc-900">{{ __('payments.checkout_title') }}</h1>
        <p class="mt-2 text-sm text-zinc-500">
            {{ __('payments.checkout_desc', ['processor' => __('payments.processor_' . $processor)]) }}
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-3 text-sm">
            <div class="rounded-xl bg-zinc-50 p-4">
                <div class="text-xs text-zinc-400">{{ __('payments.payment_method') }}</div>
                <div class="mt-1 font-semibold text-zinc-800">{{ __('payments.processor_' . $processor) }}</div>
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

        <h3 class="mt-8 text-sm font-semibold text-zinc-900">{{ __('payments.checkout_params') }}</h3>
        <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200">
            <table class="w-full text-left text-sm">
                <tbody class="divide-y divide-zinc-100">
                    @foreach($result as $key => $value)
                    <tr>
                        <th class="w-48 px-4 py-2 align-top font-mono text-xs font-medium text-zinc-500">{{ $key }}</th>
                        <td class="px-4 py-2 font-mono text-xs text-zinc-800"><pre class="whitespace-pre-wrap break-all">{{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-6 text-xs text-zinc-400">{{ __('payments.checkout_note') }}</p>
        <a href="{{ route('payments.history') }}" class="mt-4 inline-block text-sm text-brand-600 hover:underline">{{ __('payments.view_all') }}</a>
    </div>
</div>
@endsection

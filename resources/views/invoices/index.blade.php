@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('invoices.title') }}</h1>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">{{ __('invoices.invoice_number') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.date') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.amount') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-3 font-mono text-xs">INV-{{ str_pad($payment->payment_id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $payment->datetime?->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $payment->total_amount }} {{ $payment->currency }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('invoices.download', $payment) }}" class="text-brand-600 hover:underline text-xs">{{ __('invoices.download') }}</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-400">{{ __('invoices.no_invoices') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection
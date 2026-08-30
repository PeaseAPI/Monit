@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.payments_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.payments_desc') }}</p>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">{{ __('account.plan') }}</th>
                    <th class="px-4 py-3">{{ __('account.amount') }}</th>
                    <th class="px-4 py-3">{{ __('account.processor') }}</th>
                    <th class="px-4 py-3">{{ __('account.status') }}</th>
                    <th class="px-4 py-3">{{ __('account.date') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-3 font-mono text-xs">#{{ $payment->payment_id }}</td>
                    <td class="px-4 py-3">{{ $payment->plan?->name ?? $payment->plan_id }}</td>
                    <td class="px-4 py-3">{{ $payment->total_amount }} {{ $payment->currency }}</td>
                    <td class="px-4 py-3">{{ $payment->processor }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $payment->status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                            {{ $payment->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-zinc-500">{{ $payment->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        @if($payment->status === 'completed')
                        <a href="{{ route('invoices.download', $payment) }}" class="text-brand-600 hover:underline text-xs">{{ __('account.download_invoice') }}</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400">{{ __('account.no_payments') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payments->withQueryString()->links() }}</div>
</div>
@endsection
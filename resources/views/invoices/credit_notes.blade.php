@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('invoices.credit_notes_title') }}</h1>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">{{ __('invoices.date') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.amount') }}</th>
                    <th class="px-4 py-3">{{ __('invoices.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($creditNotes as $note)
                <tr class="hover:bg-zinc-50">
                    <td class="px-4 py-3 font-mono text-xs">#{{ $note->payment_id }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $note->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">{{ $note->total_amount }} {{ $note->currency }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                            {{ $note->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-400">{{ __('invoices.no_credit_notes') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $creditNotes->withQueryString()->links() }}</div>
</div>
@endsection
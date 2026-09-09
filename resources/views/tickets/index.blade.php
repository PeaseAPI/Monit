@extends('layouts.app')
@section('title', __('tickets.my_tickets'))
@section('content')
<div class="max-w-5xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('tickets.my_tickets') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('tickets.my_tickets_hint') }}</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700">+ {{ __('tickets.new_ticket') }}</a>
    </div>

    <div class="mt-8 space-y-4">
        @forelse ($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket->ticket_id) }}" class="block rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="flex items-center gap-2 font-semibold text-zinc-900">
                        <span class="font-mono text-xs text-zinc-400">{{ $ticket->code() }}</span>
                        {{ $ticket->subject }}
                    </p>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ __('tickets.category_'.$ticket->category) }} · {{ __('tickets.priority_'.$ticket->priority) }} · {{ $ticket->last_reply_at?->format('Y-m-d H:i') }}
                    </p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $ticket->status === \App\Models\Ticket::STATUS_OPEN ? 'bg-amber-100 text-amber-700' : ($ticket->status === \App\Models\Ticket::STATUS_ANSWERED ? 'bg-brand-100 text-brand-700' : 'bg-zinc-100 text-zinc-500') }}">
                    {{ __('tickets.status_'.$ticket->statusKey()) }}
                </span>
            </div>
        </a>
        @empty
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center">
            <p class="text-sm text-zinc-400">{{ __('tickets.empty') }}</p>
            <a href="{{ route('tickets.create') }}" class="mt-4 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('tickets.new_ticket') }}</a>
        </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
@endsection

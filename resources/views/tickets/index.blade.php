@extends('layouts.app')
@section('title', __('tickets.my_tickets'))
@section('content')
@php
    $statusMeta = fn ($t) => match ($t->status) {
        \App\Models\Ticket::STATUS_OPEN => ['bg-amber-100 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        \App\Models\Ticket::STATUS_ANSWERED => ['bg-brand-100 text-brand-700 ring-brand-600/20', 'bg-brand-500'],
        default => ['bg-zinc-100 text-zinc-500 ring-zinc-500/20', 'bg-zinc-400'],
    };
    $priorityDot = fn ($p) => match ($p) {
        'high' => 'bg-red-500',
        'low' => 'bg-zinc-400',
        default => 'bg-sky-500',
    };
@endphp
<div class="max-w-5xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('tickets.my_tickets') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('tickets.my_tickets_hint') }}</p>
        </div>
        <a href="{{ route('tickets.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('tickets.new_ticket') }}
        </a>
    </div>

    <div class="mt-8 space-y-3">
        @forelse ($tickets as $ticket)
        @php [$statusBadge, $statusDot] = $statusMeta($ticket); @endphp
        <a href="{{ route('tickets.show', $ticket->ticket_id) }}" class="group block rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-md hover:shadow-brand-600/5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="shrink-0 rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-[11px] font-medium text-zinc-500">{{ $ticket->code() }}</span>
                        <span class="truncate font-semibold text-zinc-900 transition group-hover:text-brand-700">{{ $ticket->subject }}</span>
                    </div>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-3.5 gap-y-1 text-xs text-zinc-500">
                        <span>{{ __('tickets.category_'.$ticket->category) }}</span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full {{ $priorityDot($ticket->priority) }}"></span>
                            {{ __('tickets.priority_'.$ticket->priority) }}
                        </span>
                        <span>{{ __('tickets.last_activity') }} {{ $ticket->last_reply_at?->format('Y-m-d H:i') }}</span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ __('tickets.status_'.$ticket->statusKey()) }}
                    </span>
                    <svg class="h-5 w-5 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-brand-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </div>
            </div>
        </a>
        @empty
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>
            </div>
            <p class="mt-4 text-sm text-zinc-400">{{ __('tickets.empty') }}</p>
            <a href="{{ route('tickets.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('tickets.new_ticket') }}</a>
        </div>
        @endforelse
    </div>

    {{ $tickets->links() }}
</div>
@endsection


@extends('layouts.admin')
@section('title', __('admin.sidebar_tickets'))
@section('content')
@php
    $statusMeta = fn ($t) => match ($t->status) {
        \App\Models\Ticket::STATUS_OPEN => ['bg-amber-100 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        \App\Models\Ticket::STATUS_ANSWERED => ['bg-brand-100 text-brand-700 ring-brand-600/20', 'bg-brand-500'],
        default => ['bg-zinc-100 text-zinc-500 ring-zinc-500/20', 'bg-zinc-400'],
    };
    $filterKeys = ['0' => 'open', '1' => 'answered', '2' => 'closed'];
@endphp
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.sidebar_tickets') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.tickets_hint') }}</p>
</div>

{{-- 状态筛选 --}}
<div class="mb-5 flex flex-wrap gap-2">
    @foreach (['' => __('tickets.filter_all'), '0' => __('tickets.status_open'), '1' => __('tickets.status_answered'), '2' => __('tickets.status_closed')] as $key => $label)
    @php $active = ($status ?? 'x') === ($key === '' ? 'x' : $key); @endphp
    <a href="{{ $key === '' ? route('admin.tickets.index') : route('admin.tickets.index', ['status' => $key]) }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-medium transition {{ $active ? 'bg-brand-600 text-white shadow-sm' : 'border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50' }}">
        {{ $label }}
        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $active ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-500' }}">{{ $counts[$key === '' ? 'all' : $filterKeys[$key]] }}</span>
    </a>
    @endforeach
</div>

<div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left text-xs tracking-wide text-zinc-500"><tr>
        <th class="px-6 py-3.5 font-medium">{{ __('tickets.subject_label') }}</th>
        <th class="px-6 py-3.5 font-medium">{{ __('admin.users') }}</th>
        <th class="px-6 py-3.5 font-medium">{{ __('tickets.category_label') }}</th>
        <th class="px-6 py-3.5 font-medium">{{ __('tickets.priority_label') }}</th>
        <th class="px-6 py-3.5 font-medium">{{ __('tickets.status_label') }}</th>
        <th class="px-6 py-3.5 font-medium">{{ __('tickets.last_activity') }}</th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($tickets as $ticket)
        @php [$statusBadge, $statusDot] = $statusMeta($ticket); @endphp
        <tr class="transition hover:bg-zinc-50/80">
            <td class="px-6 py-4">
                <a href="{{ route('admin.tickets.show', $ticket->ticket_id) }}" class="group block">
                    <span class="font-medium text-zinc-900 transition group-hover:text-brand-700">{{ $ticket->subject }}</span>
                    <span class="mt-0.5 flex items-center gap-1.5 font-mono text-[11px] text-zinc-400">
                        {{ $ticket->code() }}
                        @if ($ticket->user_id === null)
                        <span class="rounded bg-amber-100 px-1.5 py-0.5 font-sans text-[10px] font-medium text-amber-700">{{ __('tickets.guest_ticket') }}</span>
                        @endif
                    </span>
                </a>
            </td>
            <td class="px-6 py-4 text-zinc-600">{{ $ticket->user?->name ?? $ticket->email }}</td>
            <td class="px-6 py-4 text-zinc-600">{{ __('tickets.category_'.$ticket->category) }}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $ticket->priority === 'high' ? 'bg-red-100 text-red-700' : ($ticket->priority === 'low' ? 'bg-zinc-100 text-zinc-500' : 'bg-sky-100 text-sky-700') }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $ticket->priority === 'high' ? 'bg-red-500' : ($ticket->priority === 'low' ? 'bg-zinc-400' : 'bg-sky-500') }}"></span>
                    {{ __('tickets.priority_'.$ticket->priority) }}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                    {{ __('tickets.status_'.$ticket->statusKey()) }}
                </span>
            </td>
            <td class="px-6 py-4 text-zinc-500">{{ $ticket->last_reply_at?->format('Y-m-d H:i') }}</td>
        </tr>
        @empty<tr><td class="px-6 py-14 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $tickets->links() }}
@endsection


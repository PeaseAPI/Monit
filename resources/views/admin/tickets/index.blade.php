@extends('layouts.admin')
@section('title', __('admin.sidebar_tickets'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.sidebar_tickets') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.tickets_hint') }}</p>
</div>

{{-- 状态筛选 --}}
<div class="mb-5 flex flex-wrap gap-2">
    @foreach (['' => __('tickets.filter_all'), '0' => __('tickets.status_open'), '1' => __('tickets.status_answered'), '2' => __('tickets.status_closed')] as $key => $label)
    <a href="{{ $key === '' ? route('admin.tickets.index') : route('admin.tickets.index', ['status' => $key]) }}"
       class="rounded-xl px-4 py-2 text-sm font-medium transition {{ (($status ?? 'x') === ($key === '' ? 'x' : $key)) ? 'bg-brand-600 text-white' : 'border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50' }}">
        {{ $label }}
        <span class="ml-1 text-xs opacity-70">{{ $counts[$key === '' ? 'all' : ['0' => 'open', '1' => 'answered', '2' => 'closed'][$key]] }}</span>
    </a>
    @endforeach
</div>

<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr>
        <th class="px-6 py-3 font-medium text-zinc-500">ID</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('tickets.subject_label') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.users') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('tickets.category_label') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('tickets.priority_label') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('tickets.status_label') }}</th>
        <th class="px-6 py-3 font-medium text-zinc-500">{{ __('tickets.last_activity') }}</th>
    </tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($tickets as $ticket)
        <tr class="transition hover:bg-zinc-50">
            <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $ticket->code() }}</td>
            <td class="px-6 py-3">
                <a href="{{ route('admin.tickets.show', $ticket->ticket_id) }}" class="font-medium text-zinc-900 hover:text-brand-700">{{ $ticket->subject }}</a>
                @if ($ticket->user_id === null)
                <span class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-500">guest</span>
                @endif
            </td>
            <td class="px-6 py-3 text-zinc-600">{{ $ticket->user?->name ?? $ticket->email }}</td>
            <td class="px-6 py-3 text-zinc-600">{{ __('tickets.category_'.$ticket->category) }}</td>
            <td class="px-6 py-3">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $ticket->priority === 'high' ? 'bg-red-100 text-red-700' : ($ticket->priority === 'low' ? 'bg-zinc-100 text-zinc-500' : 'bg-sky-100 text-sky-700') }}">{{ __('tickets.priority_'.$ticket->priority) }}</span>
            </td>
            <td class="px-6 py-3">
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ticket->status === \App\Models\Ticket::STATUS_OPEN ? 'bg-amber-100 text-amber-700' : ($ticket->status === \App\Models\Ticket::STATUS_ANSWERED ? 'bg-brand-100 text-brand-700' : 'bg-zinc-100 text-zinc-500') }}">{{ __('tickets.status_'.$ticket->statusKey()) }}</span>
            </td>
            <td class="px-6 py-3 text-zinc-500">{{ $ticket->last_reply_at?->format('Y-m-d H:i') }}</td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="7">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $tickets->links() }}
@endsection

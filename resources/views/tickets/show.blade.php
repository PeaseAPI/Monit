@extends('layouts.app')
@section('title', __('tickets.ticket_title', ['code' => $ticket->code()]))
@section('content')
@php
    [$statusBadge, $statusDot] = match ($ticket->status) {
        \App\Models\Ticket::STATUS_OPEN => ['bg-amber-100 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        \App\Models\Ticket::STATUS_ANSWERED => ['bg-brand-100 text-brand-700 ring-brand-600/20', 'bg-brand-500'],
        default => ['bg-zinc-100 text-zinc-500 ring-zinc-500/20', 'bg-zinc-400'],
    };
    $priorityDot = match ($ticket->priority) {
        'high' => 'bg-red-500',
        'low' => 'bg-zinc-400',
        default => 'bg-sky-500',
    };
@endphp
<div class="max-w-4xl">
    <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-brand-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        {{ __('tickets.back_to_list') }}
    </a>

    {{-- 工单档案卡 --}}
    <div class="mt-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="rounded-lg bg-zinc-100 px-2 py-1 font-mono text-xs font-medium tracking-wide text-zinc-500">{{ $ticket->code() }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusBadge }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ __('tickets.status_'.$ticket->statusKey()) }}
                    </span>
                </div>
                <h1 class="mt-3 text-xl font-bold leading-snug text-zinc-900">{{ $ticket->subject }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-zinc-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z"/></svg>
                        {{ __('tickets.category_'.$ticket->category) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full {{ $priorityDot }}"></span>
                        {{ __('tickets.priority_'.$ticket->priority) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('tickets.created_at') }} {{ $ticket->datetime->format('Y-m-d H:i') }}
                    </span>
                </div>
            </div>
            @if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
            <form method="POST" action="{{ route('tickets.close', $ticket->ticket_id) }}" data-confirm="{{ __('tickets.close_confirm') }}">@csrf @method('PUT')
                <button class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-600 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50">
                    <svg class="h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    {{ __('tickets.close_ticket') }}
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- 对话流：己方消息居右（白底气泡），客服消息 brand 色气泡居左 --}}
    <div class="mt-8 space-y-5">
        @foreach ($ticket->replies as $reply)
        @php
            $author = $reply->is_staff
                ? __('tickets.staff_name', ['name' => \App\Support\Brand::name()])
                : ($reply->user?->name ?? $ticket->email);
            $initial = mb_strtoupper(mb_substr($reply->is_staff ? \App\Support\Brand::name() : ($reply->user?->name ?? $ticket->email), 0, 1, 'UTF-8'), 'UTF-8');
        @endphp
        @include('tickets.partials.message', ['reply' => $reply, 'mine' => ! $reply->is_staff, 'isStaff' => $reply->is_staff, 'author' => $author, 'initial' => $initial])
        @endforeach
    </div>

    {{-- 回复框 --}}
    @if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
    <form method="POST" action="{{ route('tickets.reply', $ticket->ticket_id) }}" class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <label for="reply-message" class="block text-sm font-semibold text-zinc-700">{{ __('tickets.reply_label') }}</label>
        <textarea name="message" id="reply-message" rows="5" required class="form-input mt-2" placeholder="{{ __('tickets.reply_placeholder') }}"></textarea>
        @error('message')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        <div class="mt-4 flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                {{ __('tickets.reply_send') }}
            </button>
        </div>
    </form>
    @else
    <div class="mt-8 flex items-center justify-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-6 text-center text-sm text-zinc-400">
        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
        {{ __('tickets.closed_notice') }}
    </div>
    @endif
</div>
@endsection


@extends('layouts.admin')
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
    <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-brand-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        {{ __('tickets.back_to_list') }}
    </a>

    {{-- 工单档案卡 + 操作 --}}
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
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        {{ $ticket->user ? $ticket->user->name.' · '.$ticket->user->email : __('tickets.guest_ticket').' · '.$ticket->email }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z"/></svg>
                        {{ __('tickets.category_'.$ticket->category) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full {{ $priorityDot }}"></span>
                        {{ __('tickets.priority_'.$ticket->priority) }}
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.tickets.status', $ticket->ticket_id) }}" class="flex items-center gap-2">@csrf @method('PUT')
                    <select name="status" class="form-input w-auto py-2">
                        @foreach (['0' => __('tickets.status_open'), '1' => __('tickets.status_answered'), '2' => __('tickets.status_closed')] as $key => $label)
                        <option value="{{ $key }}" @selected($ticket->status == $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-600 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50">{{ __('tickets.update_status') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.tickets.destroy', $ticket->ticket_id) }}" data-confirm="{{ __('common.confirm_delete') }}">@csrf @method('DELETE')
                    <button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-medium text-red-500 shadow-sm transition hover:bg-red-50">{{ __('common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>

    {{-- 对话流：客服（己方）消息居右 brand 气泡，客户消息居左 --}}
    <div class="mt-8 space-y-5">
        @foreach ($ticket->replies as $reply)
        @php
            $author = $reply->is_staff ? ($reply->user?->name ?? __('tickets.staff')) : ($reply->user?->name ?? $ticket->email);
            $initial = mb_strtoupper(mb_substr($author, 0, 1, 'UTF-8'), 'UTF-8');
        @endphp
        @include('tickets.partials.message', ['reply' => $reply, 'mine' => $reply->is_staff, 'isStaff' => $reply->is_staff, 'author' => $author, 'initial' => $initial])
        @endforeach
    </div>

    {{-- 回复框 --}}
    <form method="POST" action="{{ route('admin.tickets.reply', $ticket->ticket_id) }}" class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        <label for="reply-message" class="block text-sm font-semibold text-zinc-700">{{ __('tickets.admin_reply_label') }}</label>
        <p class="mt-1 flex items-center gap-1.5 text-xs text-zinc-400">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            {{ __('tickets.admin_reply_note') }} {{ $ticket->email }}
        </p>
        <textarea name="message" id="reply-message" rows="5" required class="form-input mt-2">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
        <div class="mt-4 flex justify-end">
            <button class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                {{ __('tickets.reply_send') }}
            </button>
        </div>
    </form>
</div>
@endsection


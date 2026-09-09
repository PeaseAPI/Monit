@extends('layouts.admin')
@section('title', __('tickets.ticket_title', ['code' => $ticket->code()]))
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('admin.tickets.index') }}" class="text-sm text-zinc-500 hover:text-brand-700">&larr; {{ __('tickets.back_to_list') }}</a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="flex flex-wrap items-center gap-2 text-2xl font-bold text-zinc-900">
                <span class="font-mono text-sm text-zinc-400">{{ $ticket->code() }}</span>
                {{ $ticket->subject }}
            </h1>
            <p class="mt-1 text-xs text-zinc-500">
                {{ $ticket->user ? __('admin.users').': '.$ticket->user->name.' ('.$ticket->user->email.')' : __('tickets.guest_ticket').': '.$ticket->email }}
                · {{ __('tickets.category_'.$ticket->category) }} · {{ __('tickets.priority_'.$ticket->priority) }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('admin.tickets.status', $ticket->ticket_id) }}" class="flex items-center gap-2">@csrf @method('PUT')
                <select name="status" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
                    @foreach (['0' => __('tickets.status_open'), '1' => __('tickets.status_answered'), '2' => __('tickets.status_closed')] as $key => $label)
                    <option value="{{ $key }}" @selected($ticket->status == $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl border border-zinc-200 px-4 py-2 text-sm text-zinc-600 transition hover:bg-zinc-50">{{ __('tickets.update_status') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.tickets.destroy', $ticket->ticket_id) }}" data-confirm="{{ __('common.confirm_delete') }}">@csrf @method('DELETE')
                <button class="rounded-xl border border-red-200 px-4 py-2 text-sm text-red-500 transition hover:bg-red-50">{{ __('common.delete') }}</button>
            </form>
        </div>
    </div>

    {{-- 对话流 --}}
    <div class="mt-8 space-y-4">
        @foreach ($ticket->replies as $reply)
        <div class="flex {{ $reply->is_staff ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] rounded-2xl border p-5 {{ $reply->is_staff ? 'border-brand-200 bg-brand-50' : 'border-zinc-200 bg-white' }}">
                <p class="flex flex-wrap items-center gap-2 text-xs font-medium text-zinc-500">
                    {{ $reply->is_staff ? ($reply->user?->name ?? __('tickets.staff')) : ($reply->user?->name ?? $ticket->email) }}
                    @if ($reply->via === 'email')
                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] text-zinc-500">email</span>
                    @endif
                    <span class="font-normal text-zinc-400">{{ $reply->datetime->format('Y-m-d H:i') }}</span>
                </p>
                <div class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-zinc-800">{{ $reply->message }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- 回复框 --}}
    <form method="POST" action="{{ route('admin.tickets.reply', $ticket->ticket_id) }}" class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6">
        @csrf
        <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.admin_reply_label') }}</label>
        <p class="mt-0.5 text-xs text-zinc-400">{{ __('tickets.admin_reply_note') }} {{ $ticket->email }}</p>
        <textarea name="message" rows="5" required class="form-input mt-2">{{ old('message') }}</textarea>
        <button class="mt-4 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('tickets.reply_send') }}</button>
    </form>
</div>
@endsection

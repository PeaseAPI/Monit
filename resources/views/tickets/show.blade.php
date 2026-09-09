@extends('layouts.app')
@section('title', __('tickets.ticket_title', ['code' => $ticket->code()]))
@section('content')
<div class="max-w-4xl">
    <a href="{{ route('tickets.index') }}" class="text-sm text-zinc-500 hover:text-brand-700">&larr; {{ __('tickets.back_to_list') }}</a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="flex flex-wrap items-center gap-2 text-2xl font-bold text-zinc-900">
                <span class="font-mono text-sm text-zinc-400">{{ $ticket->code() }}</span>
                {{ $ticket->subject }}
            </h1>
            <p class="mt-1 text-xs text-zinc-500">
                {{ __('tickets.category_'.$ticket->category) }} · {{ __('tickets.priority_'.$ticket->priority) }} · {{ __('tickets.created_at') }} {{ $ticket->datetime->format('Y-m-d H:i') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $ticket->status === \App\Models\Ticket::STATUS_OPEN ? 'bg-amber-100 text-amber-700' : ($ticket->status === \App\Models\Ticket::STATUS_ANSWERED ? 'bg-brand-100 text-brand-700' : 'bg-zinc-100 text-zinc-500') }}">
                {{ __('tickets.status_'.$ticket->statusKey()) }}
            </span>
            @if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
            <form method="POST" action="{{ route('tickets.close', $ticket->ticket_id) }}" data-confirm="{{ __('tickets.close_confirm') }}">@csrf @method('PUT')
                <button class="rounded-xl border border-zinc-200 px-4 py-2 text-sm text-zinc-600 transition hover:bg-zinc-50">{{ __('tickets.close_ticket') }}</button>
            </form>
            @endif
        </div>
    </div>

    {{-- 对话流 --}}
    <div class="mt-8 space-y-4">
        @foreach ($ticket->replies as $reply)
        <div class="flex {{ $reply->is_staff ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] rounded-2xl border p-5 {{ $reply->is_staff ? 'border-brand-200 bg-brand-50' : 'border-zinc-200 bg-white' }}">
                <p class="flex flex-wrap items-center gap-2 text-xs font-medium text-zinc-500">
                    {{ $reply->is_staff ? __('tickets.staff_name', ['name' => \App\Support\Brand::name()]) : ($reply->user?->name ?? $ticket->email) }}
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
    @if ($ticket->status !== \App\Models\Ticket::STATUS_CLOSED)
    <form method="POST" action="{{ route('tickets.reply', $ticket->ticket_id) }}" class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6">
        @csrf
        <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.reply_label') }}</label>
        <textarea name="message" rows="5" required class="form-input mt-1.5" placeholder="{{ __('tickets.reply_placeholder') }}"></textarea>
        @error('message')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        <button class="mt-4 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('tickets.reply_send') }}</button>
    </form>
    @else
    <p class="mt-8 rounded-2xl bg-zinc-50 px-6 py-5 text-center text-sm text-zinc-400">{{ __('tickets.closed_notice') }}</p>
    @endif
</div>
@endsection

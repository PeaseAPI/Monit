@extends('layouts.app', ['nav' => 'notifications'])
@section('title', __('notifications.title'))
@section('content')
<div class="max-w-4xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('notifications.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('notifications.subtitle') }}</p>
        </div>
        @if (($notifications ?? collect())->contains(fn ($n) => ! $n->is_read))
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PUT')
            <button class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('notifications.read_all') }}</button>
        </form>
        @endif
    </div>
    <div class="mt-6 space-y-3">
        @forelse ($notifications ?? [] as $n)
        <div class="flex items-start gap-4 rounded-2xl border {{ $n->is_read ? 'border-zinc-200 bg-white' : 'border-brand-200 bg-brand-50/40' }} p-4">
            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->is_read ? 'bg-zinc-200' : 'bg-brand-500' }}"></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium {{ $n->is_read ? 'text-zinc-600' : 'text-zinc-900' }}">{{ $n->data['title'] ?? $n->for_type }}</p>
                @if (! empty($n->data['description']))
                <p class="mt-0.5 text-sm text-zinc-500">{{ $n->data['description'] }}</p>
                @endif
                <p class="mt-1 text-xs text-zinc-400">{{ $n->datetime?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                @if (! $n->is_read)
                <form method="POST" action="{{ route('notifications.read', $n->internal_notification_id) }}">@csrf @method('PUT')
                    <button class="text-xs text-brand-600 hover:underline">{{ __('notifications.mark_read') }}</button>
                </form>
                @endif
                <form method="POST" action="{{ route('notifications.destroy', $n->internal_notification_id) }}">@csrf @method('DELETE')
                    <button class="text-xs text-zinc-400 transition hover:text-rose-600">{{ __('common.delete') }}</button>
                </form>
            </div>
        </div>
        @empty
        <p class="rounded-2xl border border-zinc-200 bg-white p-8 text-center text-zinc-500">{{ __('notifications.no_notifications') }}</p>
        @endforelse
    </div>
    @if (method_exists($notifications ?? null, 'previousPageUrl') && ($notifications->previousPageUrl() || $notifications->nextPageUrl()))
    <div class="mt-6 flex items-center justify-between text-sm">
        @php $prev = $notifications->previousPageUrl(); $next = $notifications->nextPageUrl(); @endphp
        <a href="{{ $prev }}" class="{{ $prev ? 'text-brand-600 hover:underline' : 'pointer-events-none text-zinc-300' }}">&larr; {{ __('common.previous') }}</a>
        <a href="{{ $next }}" class="{{ $next ? 'text-brand-600 hover:underline' : 'pointer-events-none text-zinc-300' }}">{{ __('common.next') }} &rarr;</a>
    </div>
    @endif
</div>
@endsection
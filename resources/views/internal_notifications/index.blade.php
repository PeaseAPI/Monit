@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('notifications.title') }}</h1>
    <div class="mt-6 space-y-4">
        @forelse($notifications ?? [] as $n)
        <div class="rounded-2xl border border-zinc-200 bg-white p-4 flex items-start gap-4">
            <div class="flex-1"><p class="text-sm text-zinc-900">{{ $n->message }}</p><p class="mt-1 text-xs text-zinc-500">{{ $n->datetime }}</p></div>
            @if(!$n->is_read)<form method="POST" action="{{ route('notifications.read', $n->internal_notification_id) }}">@csrf @method('PUT')<button class="text-xs text-brand-600 hover:underline">{{ __('notifications.mark_read') }}</button></form>@endif
        </div>
        @empty<p class="text-zinc-500">{{ __('notifications.no_notifications') }}</p>@endforelse
    </div>
</div>
@endsection
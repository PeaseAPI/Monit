@extends('layouts.admin')
@section('content')
<div class="max-w-4xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.broadcasts.index') }}" class="text-zinc-400 hover:text-zinc-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">{{ $broadcast->title }}</h1>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
            {{ $broadcast->status === 'sent' ? 'bg-green-50 text-green-700' : ($broadcast->status === 'pending' ? 'bg-yellow-50 text-yellow-700' : 'bg-zinc-100 text-zinc-600') }}">
            {{ $broadcast->status }}
        </span>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <div class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <span class="text-zinc-500">{{ __('admin.type') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $broadcast->type }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.target') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $broadcast->target }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.created_by') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $broadcast->user?->name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.created_at') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $broadcast->datetime?->format('Y-m-d H:i') }}</span>
            </div>
        </div>

        <div class="mt-6 border-t border-zinc-100 pt-4">
            <h3 class="text-sm font-semibold text-zinc-900 mb-2">{{ __('admin.content') }}</h3>
            <div class="prose prose-sm max-w-none text-zinc-600">{!! $broadcast->content !!}</div>
        </div>

        <div class="mt-6 flex gap-3">
            @if($broadcast->status === 'draft')
            <form method="POST" action="{{ route('admin.broadcasts.send', $broadcast->broadcast_id) }}">
                @csrf @method('PUT')
                <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.send_broadcast') }}</button>
            </form>
            @endif
            <form method="POST" action="{{ route('admin.broadcasts.duplicate', $broadcast->broadcast_id) }}">
                @csrf
                <button class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('admin.duplicate') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
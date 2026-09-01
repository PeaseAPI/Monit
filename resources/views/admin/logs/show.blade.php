@extends('layouts.admin')
@section('content')
<div class="max-w-4xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.logs.index') }}" class="text-zinc-400 hover:text-zinc-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.log_detail') }} #{{ $log->log_id }}</h1>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <div class="grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <span class="text-zinc-500">{{ __('admin.user') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $log->user?->name ?? '—' }} ({{ $log->user?->email ?? '—' }})</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.type') }}:</span>
                <span class="ml-2 font-medium text-zinc-900">{{ $log->type }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.ip') }}:</span>
                <span class="ml-2 font-mono text-xs text-zinc-900">{{ $log->ip }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.datetime') }}:</span>
                <span class="ml-2 text-zinc-900">{{ $log->datetime?->format('Y-m-d H:i:s') }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.device_type') }}:</span>
                <span class="ml-2 text-zinc-900">{{ $log->device_type ?? '—' }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.os') }}:</span>
                <span class="ml-2 text-zinc-900">{{ $log->os_name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.browser') }}:</span>
                <span class="ml-2 text-zinc-900">{{ $log->browser_name ?? '—' }}</span>
            </div>
            <div>
                <span class="text-zinc-500">{{ __('admin.location') }}:</span>
                <span class="ml-2 text-zinc-900">{{ $log->country_code ?? '—' }} {{ $log->city_name ?? '' }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('title', __('account.logs_title'))
@section('content')
<div class="p-8">
    <div class="mb-6">
        <a href="{{ route('account.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('account.logs_title') }}</h1>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.date') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('account.log_type') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('account.log_ip') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('account.log_device') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('account.log_location') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($logs as $log)
            <tr>
                <td class="px-6 py-3 text-zinc-500">{{ $log->datetime?->format('Y-m-d H:i') }}</td>
                <td class="px-6 py-3 font-medium text-zinc-900">{{ $log->type }}</td>
                <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $log->ip }}</td>
                <td class="px-6 py-3 text-zinc-500">{{ trim(($log->device_type ?? '') . ' / ' . ($log->os_name ?? '') . ' / ' . ($log->browser_name ?? ''), ' /') ?: '-' }}</td>
                <td class="px-6 py-3 text-zinc-500">{{ trim((($log->country_code ?? '') . ' ' . ($log->city_name ?? '')) ?: '-') }}</td>
            </tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('msg.no_account_logs') }}</td></tr>@endforelse
        </tbody></table>
    </div>
    {{ $logs->links() }}
</div>
@endsection
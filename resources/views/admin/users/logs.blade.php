@extends('layouts.admin')
@section('title', __('admin.user_detail'))
@section('content')
<h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.operation_logs') }}</h1>
<div class="mt-6 rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_user') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.actions') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.date') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($logs ?? [] as $log)
        <tr><td class="px-6 py-3">{{ $log->user?->email ?? '-' }}</td><td class="px-6 py-3">{{ $log->message ?? $log->action ?? '-' }}</td><td class="px-6 py-3 text-zinc-500">{{ $log->datetime }}</td></tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div>
@endsection
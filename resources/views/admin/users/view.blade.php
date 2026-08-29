@extends('layouts.admin')
@section('title', __('admin.user_detail'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $user->name }}</h1></div>
<dl class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6 divide-y divide-zinc-100">
    <div class="py-3"><dt class="text-sm font-medium text-zinc-500">ID</dt><dd class="mt-1 text-sm">{{ $user->user_id }}</dd></div>
    <div class="py-3"><dt class="text-sm font-medium text-zinc-500">{{ __('admin.col_email') }}</dt><dd class="mt-1 text-sm">{{ $user->email }}</dd></div>
    <div class="py-3"><dt class="text-sm font-medium text-zinc-500">{{ __('admin.col_register_time') }}</dt><dd class="mt-1 text-sm">{{ $user->datetime }}</dd></div>
    <div class="py-3"><dt class="text-sm font-medium text-zinc-500">{{ __('admin.user_status') }}</dt><dd class="mt-1 text-sm"><span class="rounded-full px-2 py-1 text-xs {{ $user->status ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $user->status ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></dd></div>
</dl>
<div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
    <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.account_logs') }}</h2></div>
    <div class="p-6">
        @forelse($logs ?? [] as $log)
        <div class="flex items-center gap-4 py-2 border-b border-zinc-100 last:border-0"><span class="text-sm text-zinc-700">{{ $log->message ?? $log->action ?? '-' }}</span><span class="ml-auto text-xs text-zinc-400">{{ $log->datetime }}</span></div>
        @empty<p class="text-sm text-zinc-400">{{ __('common.no_data') }}</p>@endforelse
    </div>
</div>
@endsection
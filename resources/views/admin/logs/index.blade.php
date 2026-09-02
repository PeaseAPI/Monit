@extends('layouts.admin')
@section('title', __('admin.account_logs'))
@section('content')
<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.account_logs') }}</h1></div>
    <form method="GET" class="flex flex-wrap gap-2">
        <input type="text" name="email" value="{{ request('email') }}" placeholder="{{ __('admin.email') }}" class="w-40 rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <select name="type" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
            <option value="">{{ __('admin.all_types') }}</option>
            @foreach($types as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>@endforeach
        </select>
        <button class="rounded-xl bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-700">{{ __('common.filter') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-50">CSV ↓</a>
    </form>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.type') }}</th><th class="px-6 py-3 font-medium text-zinc-500">IP</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.device') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.location') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($logs as $log)
        <tr>
                        <td class="px-6 py-3 text-zinc-500">{{ $log->log_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $log->user?->email ?? $log->user_id }}</td>
            <td class="px-6 py-3"><span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">{{ $log->type }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $log->ip }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $log->device_type }} / {{ $log->os_name }} / {{ $log->browser_name }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ trim(($log->country_code ?: '').' '.($log->city_name ?: '')) ?: '-' }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $log->datetime?->format('Y-m-d H:i:s') }}</td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <a href="{{ route('admin.logs.show', $log->log_id) }}" class="text-sm text-brand-600 hover:text-brand-700">{{ __('admin.view') }}</a>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="8">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $logs->links() }}
@endsection

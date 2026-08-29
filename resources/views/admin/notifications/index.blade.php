@extends('layouts.admin')
@section('title', __('admin.internal_notifications'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.internal_notifications') }}</h1></div>
    <a href="{{ route('admin.notifications.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('admin.notification_create') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($notifications as $n)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $n->user?->email ?? $n->user_id }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $n->data['title'] ?? '' }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $n->is_read ? 'bg-zinc-100 text-zinc-500' : 'bg-blue-50 text-blue-700' }}">{{ $n->is_read ? __('admin.notification_read') : __('admin.notification_unread') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $n->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.notifications.destroy', $n->internal_notification_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $notifications->links() }}
@endsection
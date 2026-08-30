@extends('layouts.admin')
@section('title', __('admin.push_subscribers'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.push_subscribers') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.push_subscribers_hint') }}</p>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.website') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.endpoint') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($subscribers as $s)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $s->subscriber_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $s->website?->host ?? $s->website_id }}</td>
            <td class="px-6 py-3 max-w-xs"><span class="block truncate text-xs font-mono text-zinc-500" title="{{ $s->endpoint }}">{{ $s->endpoint }}</span></td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $s->is_subscribed ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $s->is_subscribed ? __('admin.subscribed') : __('admin.unsubscribed') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $s->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.push-subscribers.destroy', $s->subscriber_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $subscribers->links() }}
@endsection

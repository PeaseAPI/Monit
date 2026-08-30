@extends('layouts.admin')
@section('title', __('admin.heatmaps'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.heatmaps') }}</h1></div>
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('common.search') }}" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <button class="rounded-xl bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-700">{{ __('common.search') }}</button>
    </form>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.name') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.website') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.path') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.snapshots') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($heatmaps as $h)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $h->heatmap_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $h->name }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $h->website?->host ?? $h->website_id }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $h->path }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $h->snapshots_count }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $h->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $h->is_enabled ? __('common.enabled') : __('common.disabled') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $h->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.heatmaps.destroy', $h->heatmap_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="8">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $heatmaps->links() }}
@endsection

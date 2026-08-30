@extends('layouts.admin')
@section('title', __('admin.replays'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.replays') }}</h1></div>
    <form method="GET" class="flex gap-2">
        <input type="number" name="website_id" value="{{ request('website_id') }}" placeholder="{{ __('admin.website_id') }}" class="w-40 rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <button class="rounded-xl bg-zinc-900 px-4 py-2 text-sm text-white hover:bg-zinc-700">{{ __('common.filter') }}</button>
    </form>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.website') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.session_id') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.offloaded') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($replays as $r)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $r->replay_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $r->website?->host ?? $r->website_id }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $r->session_id }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $r->is_offloaded ? 'bg-blue-50 text-blue-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $r->is_offloaded ? __('admin.yes') : __('admin.no') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $r->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.replays.destroy', $r->replay_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $replays->links() }}
@endsection

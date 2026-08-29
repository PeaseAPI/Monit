@extends('layouts.admin')
@section('title', __('admin.pages'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.pages') }}</h1></div>
    <a href="{{ route('admin.pages.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.position') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($pages as $p)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $p->title }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ __('admin.position_' . $p->position) }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $p->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $p->is_published ? __('msg.status_published') : __('msg.status_draft') }}</span></td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <a href="{{ route('admin.pages.edit', $p->page_id) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('admin.pages.destroy', $p->page_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="4">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $pages->links() }}
@endsection
@extends('layouts.admin')
@section('title', __('admin.blog_posts'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.blog_posts') }}</h1></div>
    <a href="{{ route('admin.blog-posts.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($posts as $p)
        <tr>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $p->title }}</td>
            <td class="px-6 py-3">
                <span class="rounded-full px-2 py-0.5 text-xs {{ $p->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $p->is_published ? __('msg.status_published') : __('msg.status_draft') }}</span>
            </td>
            <td class="px-6 py-3 text-zinc-500">{{ $p->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right whitespace-nowrap">
                <form method="POST" action="{{ route('admin.blog-posts.toggle-publish', $p->post_id) }}" class="inline">@csrf @method('PUT')<button class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ $p->is_published ? __('msg.action_unpublish') : __('msg.action_publish') }}</button></form>
                <a href="{{ route('admin.blog-posts.edit', $p->post_id) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('admin.blog-posts.destroy', $p->post_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="4">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $posts->links() }}
@endsection
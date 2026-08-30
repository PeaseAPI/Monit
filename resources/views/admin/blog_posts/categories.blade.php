@extends('layouts.admin')
@section('title', __('admin.blog_posts_categories'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.blog_posts_categories') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.blog_posts_categories_hint') }}</p>
</div>

<div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-6">
    <h2 class="mb-4 text-sm font-semibold text-zinc-900">+ {{ __('admin.category_new') }}</h2>
    <form method="POST" action="{{ route('admin.blog-posts-categories.store') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <div><label class="mb-1 block text-xs text-zinc-500">{{ __('admin.title') }}</label>
            <input type="text" name="title" required maxlength="64" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm"></div>
        <div><label class="mb-1 block text-xs text-zinc-500">URL</label>
            <input type="text" name="url" required maxlength="256" pattern="[a-z0-9-]+" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm font-mono"></div>
        <div><label class="mb-1 block text-xs text-zinc-500">{{ __('admin.order') }}</label>
            <input type="number" name="order" min="0" max="9999" value="0" class="w-24 rounded-xl border border-zinc-300 px-3 py-2 text-sm"></div>
        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.create') }}</button>
    </form>
</div>

<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">URL</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.order') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($categories as $c)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $c->category_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $c->title }}</td>
            <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $c->url }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $c->order }}</td>
            <td class="px-6 py-3 text-zinc-500">{{ $c->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.blog-posts-categories.destroy', $c->category_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $categories->links() }}
@endsection

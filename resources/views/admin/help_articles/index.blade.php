@extends('layouts.admin')
@section('title', __('admin.help_articles'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.help_articles') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.help_articles_hint') }}</p>
    </div>
    <a href="{{ route('admin.help-articles.create') }}" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('admin.article_new') }}</a>
</div>

<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.category') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.is_published') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('help.views') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($articles as $a)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $a->article_id }}</td>
            <td class="px-6 py-3">
                <p class="font-medium text-zinc-900">{{ $a->title }}</p>
                <p class="mt-0.5 font-mono text-xs text-zinc-400">{{ $a->url }}</p>
            </td>
            <td class="px-6 py-3 text-zinc-700">{{ $a->category?->title ?? '—' }}</td>
            <td class="px-6 py-3">
                <form method="POST" action="{{ route('admin.help-articles.toggle-publish', $a->article_id) }}">@csrf @method('PUT')
                    <button class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $a->is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $a->is_published ? __('common.published') : __('common.draft') }}</button>
                </form>
            </td>
            <td class="px-6 py-3 text-zinc-700">{{ $a->views }}</td>
            <td class="px-6 py-3 text-right">
                <a href="{{ route('admin.help-articles.edit', $a->article_id) }}" class="text-sm text-brand-600 hover:text-brand-700">{{ __('common.edit') }}</a>
                <form method="POST" action="{{ route('admin.help-articles.destroy', $a->article_id) }}" class="inline" data-confirm="{{ __('common.confirm_delete') }}">@csrf @method('DELETE')
                    <button class="text-sm text-red-500 hover:text-red-700">{{ __('common.delete') }}</button>
                </form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $articles->links() }}
@endsection

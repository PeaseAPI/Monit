@extends('layouts.admin')
@section('title', $article->exists ? __('common.edit') : __('admin.article_new'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ $article->exists ? __('common.edit') : __('admin.article_new') }}</h1></div>
<form method="POST" action="{{ $article->exists ? route('admin.help-articles.update', $article->article_id) : route('admin.help-articles.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    @if($article->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.title') }}</label>
        <input type="text" name="title" value="{{ old('title', $article->title) }}" required class="form-input"></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.category') }}</label>
            <select name="category_id" class="form-input mt-1">
                <option value="">—</option>
                @foreach($categories as $category)
                <option value="{{ $category->category_id }}" @selected(old('category_id', $article->category_id) == $category->category_id)>{{ $category->title }}</option>
                @endforeach
            </select></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.url_slug') }}</label>
            <input type="text" name="url" value="{{ old('url', $article->url) }}" placeholder="{{ __('admin.url_slug_hint') }}" class="form-input"></div>
    </div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.description') }}</label>
        <input type="text" name="description" value="{{ old('description', $article->description) }}" class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.content') }}</label>
        <textarea name="content" rows="12" required class="form-input font-mono">{{ old('content', $article->content) }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.order') }}</label>
            <input type="number" name="order" value="{{ old('order', $article->order ?? 0) }}" min="0" max="9999" class="form-input"></div>
        <label class="flex items-end gap-2 pb-2 text-sm text-zinc-700"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published))> {{ __('admin.is_published') }}</label>
    </div>
    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
        <a href="{{ route('admin.help-articles.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700">{{ __('common.cancel') }}</a>
    </div>
</form>
@endsection

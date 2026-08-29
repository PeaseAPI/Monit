@extends('layouts.admin')
@section('title', $post->exists ? __('admin.edit') : __('admin.create'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ $post->exists ? __('admin.edit') : __('admin.create') }}</h1></div>
<form method="POST" action="{{ $post->exists ? route('admin.blog-posts.update', $post->post_id) : route('admin.blog-posts.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    @if($post->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.title') }}</label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.url_slug') }}</label>
        <input type="text" name="url" value="{{ old('url', $post->url) }}" placeholder="{{ __('admin.url_slug_hint') }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.description') }}</label>
        <input type="text" name="description" value="{{ old('description', $post->description) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.content') }}</label>
        <textarea name="content" rows="10" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('content', $post->content) }}</textarea></div>
    <label class="flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->is_published))> {{ __('admin.is_published') }}</label>
    <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
</form>
@endsection
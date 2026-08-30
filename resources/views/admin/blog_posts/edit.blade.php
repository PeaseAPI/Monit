@extends('layouts.admin')
@section('title', __('admin.blog_posts'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.blog-posts.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_blog_post') }} - {{ $blogPost->title }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.blog-posts.update', $blogPost->blog_post_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_title') }}</label><input type="text" name="title" value="{{ old('title', $blogPost->title) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_url') }}</label><input type="text" name="url" value="{{ old('url', $blogPost->url) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-mono" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_content') }}</label><textarea name="content" rows="12" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('content', $blogPost->content) }}</textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('description', $blogPost->description) }}</textarea></div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $blogPost->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
@endsection
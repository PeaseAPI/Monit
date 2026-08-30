@extends('layouts.admin')
@section('title', __('admin.blog_posts_categories'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.blog-posts-categories.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_category') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.blog-posts-categories.update', $category->blog_posts_category_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.category_name') }}</label><input type="text" name="name" value="{{ old('name', $category->name) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
@endsection
@extends('layouts.admin')
@section('title', __('admin.blog_posts'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.blog-posts.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_blog_post') }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.blog-posts.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_title') }}</label><input type="text" name="title" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_url') }}</label><input type="text" name="url" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-mono" required placeholder="my-first-post"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_category') }}</label>
            <select name="blog_posts_category_id" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="">--</option></select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_content') }}</label><textarea name="content" rows="12" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.blog_description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></textarea></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
@endsection
@extends('layouts.admin')
@section('title', __('admin.page_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.pages.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_page') }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.pages.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_title') }}</label><input type="text" name="title" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_url') }}</label><input type="text" name="url" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-mono" required placeholder="about-us"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_content') }}</label><textarea name="content" rows="10" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_category') }}</label>
            <select name="pages_category_id" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="">--</option></select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_position') }}</label>
            <select name="position" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="top">{{ __('admin.page_position_top') }}</option><option value="bottom">{{ __('admin.page_position_bottom') }}</option><option value="none">{{ __('admin.page_position_none') }}</option></select>
        </div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
@endsection
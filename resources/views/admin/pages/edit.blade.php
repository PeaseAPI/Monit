@extends('layouts.admin')
@section('title', __('admin.page_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.pages.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_page') }} - {{ $page->title }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.pages.update', $page->page_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_title') }}</label><input type="text" name="title" value="{{ old('title', $page->title) }}" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_url') }}</label><input type="text" name="url" value="{{ old('url', $page->url) }}" class="form-input font-mono" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_content') }}</label><textarea name="content" rows="10" class="form-input">{{ old('content', $page->content) }}</textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.page_position') }}</label>
            <select name="position" class="form-input">
                <option value="top" {{ old('position', $page->position) === 'top' ? 'selected' : '' }}>{{ __('admin.page_position_top') }}</option>
                <option value="bottom" {{ old('position', $page->position) === 'bottom' ? 'selected' : '' }}>{{ __('admin.page_position_bottom') }}</option>
                <option value="none" {{ old('position', $page->position) === 'none' ? 'selected' : '' }}>{{ __('admin.page_position_none') }}</option>
            </select>
        </div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $page->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
@endsection
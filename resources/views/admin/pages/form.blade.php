@extends('layouts.admin')
@section('title', $page->exists ? __('admin.edit') : __('admin.create'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ $page->exists ? __('admin.edit') : __('admin.create') }}</h1></div>
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page->page_id) : route('admin.pages.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    @if($page->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.title') }}</label>
        <input type="text" name="title" value="{{ old('title', $page->title) }}" required class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.url_slug') }}</label>
        <input type="text" name="url" value="{{ old('url', $page->url) }}" placeholder="{{ __('admin.url_slug_hint') }}" class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.description') }}</label>
        <input type="text" name="description" value="{{ old('description', $page->description) }}" class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.position') }}</label>
        <select name="position" class="form-input">
            @foreach(['none', 'header', 'footer'] as $pos)
            <option value="{{ $pos }}" @selected(old('position', $page->position ?? 'none'))>{{ __('admin.position_' . $pos) }}</option>
            @endforeach
        </select></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.content') }}</label>
        <textarea name="content" rows="10" required class="form-input">{{ old('content', $page->content) }}</textarea></div>
    <label class="flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> {{ __('admin.is_published') }}</label>
    <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
</form>
@endsection
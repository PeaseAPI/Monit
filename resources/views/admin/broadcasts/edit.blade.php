@extends('layouts.admin')
@section('title', __('admin.broadcasts'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.broadcasts.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_broadcast') }} - {{ $broadcast->name }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.broadcasts.update', $broadcast->broadcast_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_name') }}</label><input type="text" name="name" value="{{ old('name', $broadcast->name) }}" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_subject') }}</label><input type="text" name="subject" value="{{ old('subject', $broadcast->subject) }}" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_content') }}</label><textarea name="content" rows="10" class="form-input">{{ old('content', $broadcast->content) }}</textarea></div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $broadcast->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
@endsection
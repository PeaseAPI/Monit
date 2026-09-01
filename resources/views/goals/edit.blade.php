@extends('layouts.app')
@section('title', __('stats.edit_goal'))
@section('content')
<div class="py-8">
    <div class="mb-6"><a href="{{ route('goals.index', $website) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.edit_goal') }} - {{ $goal->name }}</h1></div>
    <div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('goals.update', [$website, $goal]) }}">@csrf @method('PUT')
        <div class="space-y-4">
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.goal_name') }}</label><input type="text" name="name" value="{{ old('name', $goal->name) }}" class="form-input" required></div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.goal_type') }}</label>
                <select name="type" class="form-input">
                    <option value="path" {{ old('type', $goal->type) === 'path' ? 'selected' : '' }}>{{ __('stats.goal_type_path') }}</option>
                    <option value="event" {{ old('type', $goal->type) === 'event' ? 'selected' : '' }}>{{ __('stats.goal_type_event') }}</option>
                    <option value="scroll_depth" {{ old('type', $goal->type) === 'scroll_depth' ? 'selected' : '' }}>{{ __('stats.goal_type_scroll') }}</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.goal_path') }}</label><input type="text" name="path" value="{{ old('path', $goal->path) }}" class="form-input"></div>
            <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $goal->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
        </div>
        </form>
    </div>
</div>
@endsection
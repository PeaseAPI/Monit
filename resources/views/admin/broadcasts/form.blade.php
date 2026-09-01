@extends('layouts.admin')
@section('title', $broadcast->exists ? __('admin.edit') : __('admin.create'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ $broadcast->exists ? __('admin.edit') : __('admin.create') }}</h1></div>
<form method="POST" action="{{ $broadcast->exists ? route('admin.broadcasts.update', $broadcast->broadcast_id) : route('admin.broadcasts.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    @if($broadcast->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.title') }}</label>
        <input type="text" name="title" value="{{ old('title', $broadcast->title) }}" required class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_type') }}</label>
        <select name="type" class="form-input">
            @foreach(['email', 'push'] as $type)
            <option value="{{ $type }}" @selected(old('type', $broadcast->type ?? 'email'))>{{ __('admin.broadcast_type_' . $type) }}</option>
            @endforeach
        </select></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_target') }}</label>
        <select name="target" class="form-input">
            @foreach(['all', 'newsletter', 'plan'] as $target)
            <option value="{{ $target }}" @selected(old('target', $broadcast->target ?? 'all'))>{{ __('admin.broadcast_target_' . $target) }}</option>
            @endforeach
        </select></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_plan') }}</label>
        <select name="target_plan_id" class="form-input">
            <option value="">--</option>
            @foreach($plans as $plan)
            <option value="{{ $plan->plan_id }}" @selected(old('target_plan_id', $broadcast->target_plan_id))>{{ $plan->name }}</option>
            @endforeach
        </select></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.broadcast_scheduled_at') }}</label>
        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($broadcast->scheduled_at)->format('Y-m-d\\TH:i')) }}" class="form-input"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.content') }}</label>
        <textarea name="content" rows="8" required class="form-input">{{ old('content', $broadcast->content) }}</textarea></div>
    <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
</form>
@endsection
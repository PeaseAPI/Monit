@extends('layouts.admin')
@section('title', $code->exists ? __('admin.edit') : __('admin.create'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ $code->exists ? __('admin.edit') : __('admin.create') }}</h1></div>
<form method="POST" action="{{ $code->exists ? route('admin.codes.update', $code->code_id) : route('admin.codes.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    @if($code->exists) @method('PUT') @endif
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_name') }}</label>
        <input type="text" name="name" value="{{ old('name', $code->name) }}" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_code') }}</label>
        <input type="text" name="code" value="{{ old('code', $code->code ?? ($codeValue ?? '')) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 font-mono text-sm uppercase"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_type') }}</label>
        <select name="type" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
            <option value="discount" @selected(old('type', $code->type ?? 'discount'))>{{ __('admin.code_type_discount') }}</option>
            <option value="plan" @selected(old('type', $code->type))>{{ __('admin.code_type_plan') }}</option>
        </select></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_plan') }}</label>
        <select name="plan_id" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
            <option value="">--</option>
            @foreach($plans as $plan)
            <option value="{{ $plan->plan_id }}" @selected(old('plan_id', $code->plan_id))>{{ $plan->name }}</option>
            @endforeach
        </select></div>
    <div class="grid grid-cols-3 gap-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_days') }}</label>
            <input type="number" name="days" min="0" value="{{ old('days', $code->days ?? 0) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_discount') }}(%)</label>
            <input type="number" name="discount" step="0.01" min="0" max="100" value="{{ old('discount', $code->discount ?? 0) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_max') }}</label>
            <input type="number" name="max_redemptions" min="0" value="{{ old('max_redemptions', $code->max_redemptions) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_start') }}</label>
            <input type="datetime-local" name="date_start" value="{{ old('date_start', optional($code->date_start)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_end') }}</label>
            <input type="datetime-local" name="date_end" value="{{ old('date_end', optional($code->date_end)->format('Y-m-d\\TH:i')) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    </div>
    <label class="flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $code->is_enabled ?? true))> {{ __('admin.code_enabled') }}</label>
    <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
</form>
@endsection
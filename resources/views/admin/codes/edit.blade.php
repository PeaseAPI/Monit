@extends('layouts.admin')
@section('title', __('admin.edit_code'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.codes.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_code') }} - {{ $code->name }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.codes.update', $code->code_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_name') }}</label><input type="text" name="name" value="{{ old('name', $code->name) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_code') }}</label><input type="text" name="code" value="{{ old('code', $code->code) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-mono" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_type') }}</label>
            <select name="type" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" id="code-type">
                <option value="discount" {{ old('type', $code->type) === 'discount' ? 'selected' : '' }}>{{ __('admin.code_type_discount') }}</option>
                <option value="plan" {{ old('type', $code->type) === 'plan' ? 'selected' : '' }}>{{ __('admin.code_type_plan') }}</option>
            </select>
        </div>
        <div id="discount-field" class="{{ old('type', $code->type) === 'plan' ? 'hidden' : '' }}"><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_discount') }} (%)</label><input type="number" name="discount" min="0" max="100" value="{{ old('discount', $code->discount) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div id="plan-field" class="{{ old('type', $code->type) !== 'plan' ? 'hidden' : '' }}">
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_plan') }}</label><input type="text" name="plan_id" value="{{ old('plan_id', $code->plan_id) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_days') }}</label><input type="number" name="days" min="0" value="{{ old('days', $code->days) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_max_redemptions') }}</label><input type="number" name="max_redemptions" min="0" value="{{ old('max_redemptions', $code->max_redemptions) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $code->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
<script>document.getElementById('code-type').addEventListener('change',function(){document.getElementById('discount-field').classList.toggle('hidden',this.value==='plan');document.getElementById('plan-field').classList.toggle('hidden',this.value!=='plan');});</script>
@endsection
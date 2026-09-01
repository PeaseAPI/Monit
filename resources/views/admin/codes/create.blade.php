@extends('layouts.admin')
@section('title', __('admin.create_code'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.codes.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_code') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.codes.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_name') }}</label><input type="text" name="name" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_code') }}</label><input type="text" name="code" class="form-input font-mono" required placeholder="{{ \Illuminate\Support\Str::random(12) }}"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_type') }}</label>
            <select name="type" class="form-input" id="code-type">
                <option value="discount">{{ __('admin.code_type_discount') }}</option>
                <option value="plan">{{ __('admin.code_type_plan') }}</option>
            </select>
        </div>
        <div id="discount-field"><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_discount') }} (%)</label><input type="number" name="discount" min="0" max="100" class="form-input" value="0"></div>
        <div id="plan-field" class="hidden">
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_plan') }}</label><input type="text" name="plan_id" class="form-input"></div>
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_days') }}</label><input type="number" name="days" min="0" class="form-input" value="30"></div>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.code_max_redemptions') }}</label><input type="number" name="max_redemptions" min="0" class="form-input" value="0" placeholder="0 = 无限"></div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" checked class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
<script>document.getElementById('code-type').addEventListener('change',function(){document.getElementById('discount-field').classList.toggle('hidden',this.value==='plan');document.getElementById('plan-field').classList.toggle('hidden',this.value!=='plan');});</script>
@endsection
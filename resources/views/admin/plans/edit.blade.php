@extends('layouts.admin')
@section('title', __('admin.plan_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.plans.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ 'Edit Plan' }} - {{ $plan->name }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.plans.update', $plan->plan_id) }}">@csrf @method('PUT')
    <div class="grid gap-4 md:grid-cols-2">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.plan_name_col') }}</label><input type="text" name="name" value="{{ old('name', $plan->name) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">月价（{{ config('monit.payment.default_currency', 'USD') }}）</label><input type="number" step="0.01" name="price" value="{{ old('price', $plan->prices['monthly'] ?? 0) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">排序</label><input type="number" name="order" value="{{ old('order', $plan->order ?? 0) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div class="flex items-end"><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" @if(old('is_enabled', $plan->is_enabled)) checked @endif class="h-4 w-4 rounded border-zinc-300 text-brand-600"> 启用该套餐</label></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium text-zinc-700">描述</label><textarea name="description" rows="2" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('description', $plan->description) }}</textarea></div>
    </div>

    <h2 class="mt-8 text-lg font-semibold text-zinc-900">功能矩阵（规格书 §10.2）</h2>
    @include('admin.plans._feature-matrix', ['settings' => old('features', $plan->settings ?? [])])

    <div class="mt-8"><button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.save') }}</button></div>
    </form>
</div>
@endsection

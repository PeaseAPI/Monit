@extends('layouts.admin')
@section('title', __('admin.plan_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.plans.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ 'Create Plan' }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.plans.store') }}">@csrf
    <div class="grid gap-4 md:grid-cols-2">
        <div><label class="block text-sm font-medium text-zinc-700">套餐 ID</label><input type="text" name="plan_id" class="form-input" required placeholder="pro"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.plan_name_col') }}</label><input type="text" name="name" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">月价（{{ \App\Support\Currency::default() }}，年价 = x12，终身 = x10）</label><input type="number" step="0.01" name="price" value="9" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">排序</label><input type="number" name="order" value="0" class="form-input"></div>
        <div class="md:col-span-2"><label class="block text-sm font-medium text-zinc-700">描述</label><textarea name="description" rows="2" class="form-input"></textarea></div>
    </div>

    <h2 class="mt-8 text-lg font-semibold text-zinc-900">功能矩阵（规格书 §10.2）</h2>
    @include('admin.plans._feature-matrix', ['settings' => []])

    <div class="mt-8"><button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button></div>
    </form>
</div>
@endsection

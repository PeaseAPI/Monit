@extends('layouts.admin')
@section('title', __('admin.tax_edit'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.taxes.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.tax_edit') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.taxes.update', $tax->tax_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.tax_country') }}</label><input type="text" name="country" value="{{ old('country', $tax->country) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.tax_rate') }}</label><input type="number" step="0.01" name="rate" value="{{ old('rate', $tax->rate) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.save') }}</button>
    </div></form>
</div>
@endsection
@extends('layouts.admin')
@section('title', __('admin.tax_list'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.tax_list') }}</h1></div>
    <a href="{{ route('admin.taxes.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.tax_country') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.tax_rate') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($taxes ?? [] as $t)<tr><td class="px-6 py-3">{{ $t->country }}</td><td class="px-6 py-3">{{ $t->rate }}%</td></tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="2">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
@endsection
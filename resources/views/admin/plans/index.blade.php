@extends('layouts.admin')
@section('title', __('admin.plan_list'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.plan_list') }}</h1></div>
    <a href="{{ route('admin.plans.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700"+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.plan_name_col') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.plan_price_col') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user_status') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($plans ?? [] as $p)<tr><td class="px-6 py-3">{{ $p->name }}</td><td class="px-6 py-3">¥{{ $p->price }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $p->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $p->is_active ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td></tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_plans') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
@endsection
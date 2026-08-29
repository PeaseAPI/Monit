@extends('layouts.admin')
@section('title', __('admin.website_list'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.website_list') }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.website_name_col') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.domain_host_col') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.user_status') }}</th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($websites ?? [] as $w)<tr><td class="px-6 py-3">{{ $w->name }}</td><td class="px-6 py-3 text-zinc-500">{{ $w->domain }}</td><td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $w->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $w->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td></tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
@endsection
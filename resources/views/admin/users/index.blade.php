@extends('layouts.admin')
@section('title', __('admin.user_management'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.user_management') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.users_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        {{ __('common.add') }}
    </a>
</div>

{{-- 筛选工具栏（对标原版：搜索 + 套餐 + 状态） --}}
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
    <div class="relative min-w-0 flex-1 sm:max-w-xs">
        <svg class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_name_email') }}" class="form-input pl-10">
    </div>
    <select name="plan" class="form-select w-auto min-w-36">
        <option value="">{{ __('admin.filter_all_plans') }}</option>
        @foreach ($plans ?? [] as $p)
            <option value="{{ $p->plan_id }}" {{ request('plan') == $p->plan_id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-select w-auto min-w-28">
        <option value="">{{ __('admin.filter_all_status') }}</option>
        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('admin.status_active') }}</option>
        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('admin.status_unconfirmed') }}</option>
        <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>{{ __('admin.status_disabled') }}</option>
    </select>
    <select name="type" class="form-select w-auto min-w-28">
        <option value="">{{ __('admin.filter_all_types') }}</option>
        <option value="0" {{ request('type') === '0' ? 'selected' : '' }}>{{ __('admin.type_user') }}</option>
        <option value="1" {{ request('type') === '1' ? 'selected' : '' }}>{{ __('admin.type_admin') }}</option>
    </select>
    <button type="submit" class="btn btn-secondary">{{ __('admin.filter') }}</button>
    @if (request('search') || request('plan') || request('status') !== null)
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('admin.reset') }}</a>
    @endif
</form>
{{-- 用户表（对标原版列：用户/套餐/状态/注册时间/操作） --}}
<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('admin.col_user') }}</th>
                    <th>{{ __('admin.col_plan') }}</th>
                    <th>{{ __('admin.user_type') }}</th>
                    <th>{{ __('admin.user_status') }}</th>
                    <th>{{ __('admin.col_date') }}</th>
                    <th class="text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($users ?? [] as $u)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold text-white">{{ mb_substr($u->name, 0, 1) }}</span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-900">{{ $u->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge-soft bg-brand-50 text-brand-700">{{ $u->plan_id }}</span></td>
                    <td>
                        @if ($u->type == 1)<span class="badge-soft bg-violet-50 text-violet-700">{{ __('admin.type_admin') }}</span>@else<span class="badge-soft bg-zinc-50 text-zinc-600">{{ __('admin.type_user') }}</span>@endif
                    </td>
                    <td>
                        @if ($u->status == 1)
                            <span class="badge-soft bg-emerald-50 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('admin.status_active') }}</span>
                        @elseif ($u->status == 2)
                            <span class="badge-soft bg-red-50 text-red-700"><span class="h-1.5 w-1.5 rounded-full bg-red-400"></span>{{ __('admin.status_disabled') }}</span>
                        @else
                            <span class="badge-soft bg-amber-50 text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>{{ __('admin.status_unconfirmed') }}</span>
                        @endif
                    </td>
                    <td class="text-zinc-500">{{ optional($u->created_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.users.view', $u->user_id) }}" class="rounded-lg p-2 text-zinc-400 transition hover:bg-brand-50 hover:text-brand-600" title="{{ __('common.view') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <a href="{{ route('admin.users.edit', $u->user_id) }}" class="rounded-lg p-2 text-zinc-400 transition hover:bg-brand-50 hover:text-brand-600" title="{{ __('common.edit') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-400">{{ __('admin.no_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (($users ?? null) && $users->hasPages())
        <div class="border-t border-zinc-100 px-6 py-4">{{ $users->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

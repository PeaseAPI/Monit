@extends('layouts.admin')
@section('title', __('admin.website_list'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.website_list') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.websites_subtitle') }}</p>
</div>

{{-- 搜索工具栏 --}}
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
    <div class="relative min-w-0 flex-1 sm:max-w-xs">
        <svg class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_website_user') }}" class="form-input pl-10">
    </div>
    <button type="submit" class="btn btn-secondary">{{ __('admin.filter') }}</button>
    @if (request('search'))
        <a href="{{ route('admin.websites.index') }}" class="btn btn-ghost">{{ __('admin.reset') }}</a>
    @endif
</form>

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('admin.website_name_col') }}</th>
                    <th>{{ __('admin.col_user') }}</th>
                    <th>{{ __('admin.col_tracking_type') }}</th>
                    <th>{{ __('admin.col_status') }}</th>
                    <th>{{ __('admin.col_date') }}</th>
                    <th class="text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($websites ?? [] as $w)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-900">{{ $w->name }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $w->scheme ?? 'https' }}://{{ $w->host }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="text-zinc-500">{{ optional($w->user)->name ?? '—' }}</td>
                    <td>
                        @if ($w->isLightweight())
                            <span class="badge-soft bg-amber-50 text-amber-700">{{ __('websites.lightweight_mode_label') }}</span>
                        @else
                            <span class="badge-soft bg-brand-50 text-brand-700">{{ __('websites.advanced_mode_label') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($w->is_enabled)
                            <span class="badge-soft bg-emerald-50 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('msg.status_enabled') }}</span>
                        @else
                            <span class="badge-soft bg-zinc-100 text-zinc-500"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __('msg.status_disabled') }}</span>
                        @endif
                    </td>
                    <td class="text-zinc-500">{{ optional($w->datetime)->format('Y-m-d H:i') }}</td>
                    <td>
                        <div class="flex items-center justify-end">
                            <form method="POST" action="{{ route('admin.websites.toggle_status', $w->website_id) }}" onsubmit="return confirm(this.dataset.msg)" data-msg="{{ $w->is_enabled ? __('admin.disable_confirm') : __('admin.enable_confirm') }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn btn-secondary px-3 py-1.5 text-xs">{{ $w->is_enabled ? __('admin.disable') : __('admin.enable') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (($websites ?? null) && $websites->hasPages())
        <div class="border-t border-zinc-100 px-6 py-4">{{ $websites->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

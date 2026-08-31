@extends('layouts.admin')
@section('title', __('admin.domain_list'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.domain_list') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.domains_subtitle') }}</p>
</div>

{{-- 搜索工具栏 --}}
<form method="GET" class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm">
    <div class="relative min-w-0 flex-1 sm:max-w-xs">
        <svg class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_domain_user') }}" class="form-input pl-10">
    </div>
    <button type="submit" class="btn btn-secondary">{{ __('admin.filter') }}</button>
    @if (request('search'))
        <a href="{{ route('admin.domains.index') }}" class="btn btn-ghost">{{ __('admin.reset') }}</a>
    @endif
    <a href="{{ route('admin.domains.create') }}" class="btn ml-auto bg-gradient-to-r from-brand-600 to-brand-700 text-white shadow-md shadow-brand-600/20 hover:from-brand-700 hover:to-brand-800">{{ __('common.add') }}</a>
</form>

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('admin.col_domain') }}</th>
                    <th>{{ __('admin.col_user') }}</th>
                    <th>{{ __('admin.col_type') }}</th>
                    <th>{{ __('admin.col_status') }}</th>
                    <th>{{ __('admin.col_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($domains ?? [] as $d)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                            </span>
                            <span class="font-medium text-zinc-900">{{ $d->scheme ?? 'https' }}://{{ $d->host }}</span>
                        </div>
                    </td>
                    <td class="text-zinc-500">{{ optional($d->user)->name ?? '—' }}</td>
                    <td>
                        @if ($d->type === 1)
                            <span class="badge-soft bg-sky-50 text-sky-700">{{ __('admin.domain_type_main') }}</span>
                        @else
                            <span class="badge-soft bg-zinc-100 text-zinc-600">{{ __('admin.domain_type_custom') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($d->is_enabled)
                            <span class="badge-soft bg-emerald-50 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('msg.status_enabled') }}</span>
                        @else
                            <span class="badge-soft bg-zinc-100 text-zinc-500"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __('msg.status_disabled') }}</span>
                        @endif
                    </td>
                    <td class="text-zinc-500">{{ optional($d->datetime)->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-12 text-center text-zinc-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if (($domains ?? null) && $domains->hasPages())
        <div class="border-t border-zinc-100 px-6 py-4">{{ $domains->appends(request()->query())->links() }}</div>
    @endif
</div>
@endsection

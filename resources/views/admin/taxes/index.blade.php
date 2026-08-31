@extends('layouts.admin')
@section('title', __('admin.tax_list'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.tax_list') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.taxes_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.taxes.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        {{ __('common.add') }}
    </a>
</div>

<div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('admin.tax_name_col') }}</th>
                    <th>{{ __('admin.tax_value_col') }}</th>
                    <th>{{ __('admin.tax_type_col') }}</th>
                    <th class="text-right">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($taxes ?? [] as $t)
                <tr>
                    <td class="font-medium text-zinc-900">{{ $t->name }}</td>
                    <td><span class="text-lg font-bold tabular-nums text-zinc-900">{{ rtrim(rtrim(number_format((float) $t->value, 1, '.', ''), '0'), '.') }}%</span></td>
                    <td>
                        @if ($t->type === 'inclusive')
                            <span class="badge-soft bg-emerald-50 text-emerald-700">{{ __('admin.tax_type_inclusive') }}</span>
                        @else
                            <span class="badge-soft bg-amber-50 text-amber-700">{{ __('admin.tax_type_exclusive') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.taxes.edit', $t->tax_id) }}" class="btn btn-secondary px-3 py-1.5 text-xs">{{ __('common.edit') }}</a>
                            <form method="POST" action="{{ route('admin.taxes.destroy', $t->tax_id) }}" onsubmit="return confirm(this.dataset.msg)" data-msg="{{ __('admin.tax_delete_confirm') }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">{{ __('common.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-12 text-center text-zinc-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

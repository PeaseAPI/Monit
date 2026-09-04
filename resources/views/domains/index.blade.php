@extends('layouts.app', ['nav' => 'domains'])
@section('title', __('domains.title'))
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('domains.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">{{ __('domains.subtitle') }}</p>
        </div>
        <a href="{{ route('domains.create') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ __('domains.add') }}</a>
    </div>
    <div class="mt-6 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('domains.domain') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('domains.monitor') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('domains.expiration') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('domains.registrar') }}</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse ($domains ?? [] as $d)
            <tr>
                <td class="px-6 py-3 font-medium text-zinc-900"><a href="{{ route('domains.show', $d->domain_id) }}" class="hover:underline">{{ $d->host }}</a></td>
                <td class="px-6 py-3"><span class="rounded-full px-2 py-1 text-xs {{ $d->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $d->is_enabled ? __('domains.status_enabled') : __('domains.status_disabled') }}</span></td>
                <td class="px-6 py-3">
                    <form method="POST" action="{{ route('domains.update') }}" class="inline">@csrf @method('PUT')
                        <input type="hidden" name="domain_id" value="{{ $d->domain_id }}">
                        <input type="hidden" name="monitor_is_enabled" value="{{ $d->monitor_is_enabled ? 0 : 1 }}">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs transition {{ $d->monitor_is_enabled ? 'bg-brand-50 text-brand-700 hover:bg-brand-100' : 'border border-zinc-200 text-zinc-500 hover:bg-zinc-50' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $d->monitor_is_enabled ? 'bg-brand-500' : 'bg-zinc-300' }}"></span>
                            {{ $d->monitor_is_enabled ? __('domains.monitor_on') : __('domains.monitor_off') }}
                        </button>
                    </form>
                </td>
                <td class="px-6 py-3 text-zinc-600">
                    @if ($d->monitor_expiration_date)
                        {{ $d->monitor_expiration_date->format('Y-m-d') }}
                        @php $daysLeft = (int) now()->startOfDay()->diffInDays($d->monitor_expiration_date, false); @endphp
                        <span class="ml-1 text-xs {{ $daysLeft <= 30 ? 'text-rose-600' : 'text-zinc-400' }}">{{ $daysLeft >= 0 ? trans_choice('domains.days_left', $daysLeft, ['count' => $daysLeft]) : __('domains.expired') }}</span>
                    @else
                        <span class="text-zinc-400">{{ __('domains.no_monitor_data') }}</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-zinc-600">{{ $d->monitor_registrar ?: '—' }}</td>
                <td class="px-6 py-3 text-right">
                    <form method="POST" action="{{ route('domains.destroy', $d->domain_id) }}" onsubmit="return confirm('{{ __('domains.delete_confirm') }}')">@csrf @method('DELETE')
                        <button type="submit" class="text-xs text-zinc-400 transition hover:text-rose-600">{{ __('common.delete') }}</button>
                    </form>
                </td>
            </tr>
            @empty<tr><td class="px-6 py-10 text-center text-zinc-500" colspan="6">{{ __('common.no_domains') }}</td></tr>@endforelse
        </tbody></table>
    </div>
    @if (($domains ?? collect())->isNotEmpty())
    <p class="mt-3 text-xs text-zinc-400">{{ __('domains.monitor_hint') }}</p>
    @endif
</div>
@endsection
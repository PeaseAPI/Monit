@extends('layouts.app', ['nav' => 'websites'])

@section('title', __('admin.website_list'))

@section('content')
    @php
        $planSettings = auth()->user()->getPlanSettings();
        $limit = $planSettings['websites_limit'] ?? -1;
    @endphp
    <div class="flex items-center justify-between">
        <div>
                        <h2 class="text-2xl font-bold">{{ __('websites.management_title') }}</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ __('websites.management_desc', ['limit' => $limit === -1 ? __('websites.unlimited') : count($websites).' / '.$limit]) }}</p>
        </div>
        @if ($limit === -1 || count($websites) < $limit)
            <a href="{{ route('websites.create') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                {{ __('dashboard.add_website') }}
            </a>
        @endif
    </div>

    @if ($websites->isEmpty())
        <div class="mt-6 rounded-2xl border border-dashed border-zinc-300 bg-white p-12 text-center">
            <p class="text-sm text-zinc-500">{{ __('websites.no_websites_hint') }}</p>
        </div>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 bg-zinc-50 text-left text-xs font-medium tracking-wider text-zinc-500 uppercase">
                        <th class="px-6 py-3.5">{{ __('admin.website_name_col') }}</th>
                        <th class="px-6 py-3.5">{{ __('websites.mode_col') }}</th>
                        <th class="px-6 py-3.5">{{ __('websites.pixel_key_col') }}</th>
                        <th class="px-6 py-3.5">{{ __('admin.user_status') }}</th>
                        <th class="px-6 py-3.5 text-right">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($websites as $website)
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="px-6 py-4">
                                <a href="{{ route('dashboard', ['website_id' => $website->website_id]) }}" class="font-medium text-zinc-900 hover:text-brand-600">{{ $website->name }}</a>
                                <p class="mt-0.5 text-xs text-zinc-400">{{ $website->host }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $website->isLightweight() ? 'bg-amber-100 text-amber-700' : 'bg-brand-100 text-brand-700' }}">
                                    {{ $website->isLightweight() ? __('websites.lightweight') : __('websites.advanced') }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs text-zinc-600">{{ \Illuminate\Support\Str::limit($website->pixel_key, 14) }}</code>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $website->is_enabled ? 'text-emerald-600' : 'text-zinc-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $website->is_enabled ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                    {{ $website->is_enabled ? __('websites.tracking') : __('websites.paused') }}
                                </span>
                                <p class="mt-0.5 text-xs text-zinc-400">{{ __('websites.total_views') }} {{ number_format($website->events_count) }}</p>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('dashboard.install', $website->website_id) }}" class="font-medium text-zinc-600 hover:text-brand-600">{{ __('websites.install') }}</a>
                                <span class="mx-2 text-zinc-200">|</span>
                                <a href="{{ route('websites.edit', $website->website_id) }}" class="font-medium text-zinc-600 hover:text-brand-600">{{ __('common.edit') }}</a>
                                <span class="mx-2 text-zinc-200">|</span>
                                <form method="POST" action="{{ route('websites.destroy', $website->website_id) }}" class="inline" onsubmit="return confirm('{{ __('websites.confirm_delete') }} {{ $website->name }}? {{ __('websites.confirm_delete_warning') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-500 hover:text-red-600">{{ __('common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

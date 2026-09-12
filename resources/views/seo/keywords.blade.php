@extends('layouts.app')
@section('title', __('seo.keywords_title'))
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.keywords_title') }}</h1>
        @unless ($autoEnabled)
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs text-amber-700">{{ __('seo.serpapi_not_configured_note') }}</span>
        @endunless
    </div>

    {{-- 汇总卡 --}}
    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-5">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">{{ __('seo.keywords_tracked') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900">{{ number_format($summary['tracked']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">Top 3</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $summary['top3'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">Top 10</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ $summary['top10'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">Top 100</p>
            <p class="mt-1 text-2xl font-bold text-zinc-600">{{ $summary['top100'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">{{ __('seo.avg_position') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900">{{ $summary['avg'] ?? '—' }}</p>
        </div>
    </div>

    {{-- 添加表单 --}}
    <form method="POST" action="{{ route('seo.keywords.store') }}" class="mt-6 rounded-2xl border border-zinc-200 bg-white p-4">
        @csrf
        <div class="grid gap-3 md:grid-cols-6">
            <input type="text" name="keyword" required maxlength="256" placeholder="{{ __('seo.keyword_placeholder') }}" value="{{ old('keyword') }}"
                   class="md:col-span-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <select name="website_id" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="">{{ __('seo.choose_website') }}</option>
                @foreach ($websites as $site)
                    <option value="{{ $site->website_id }}" @selected(old('website_id') == $site->website_id)>{{ $site->host }}</option>
                @endforeach
            </select>
            <select name="search_engine" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="google">Google</option>
                <option value="bing">Bing</option>
                <option value="baidu">Baidu</option>
            </select>
            <select name="device" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="desktop">{{ __('seo.desktop') }}</option>
                <option value="mobile">{{ __('seo.mobile') }}</option>
            </select>
            <select name="check_interval" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="daily">{{ __('seo.interval_daily') }}</option>
                <option value="weekly" selected>{{ __('seo.interval_weekly') }}</option>
                <option value="monthly">{{ __('seo.interval_monthly') }}</option>
                <option value="never">{{ __('seo.interval_never') }}</option>
            </select>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <input type="url" name="target_url" placeholder="{{ __('seo.target_url_placeholder') }}" value="{{ old('target_url') }}"
                   class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.add_keyword') }}</button>
        </div>
        @error('keyword')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </form>


    {{-- 关键词表 --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
            <tr>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.keyword') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.search_engine') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.current_position') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.best_position') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.last_checked') }}</th>
                <th class="px-6 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse ($keywords as $kw)
                <tr class="{{ $kw->is_enabled ? '' : 'opacity-50' }}">
                    <td class="px-6 py-3">
                        <details>
                            <summary class="cursor-pointer list-none">
                                <p class="font-medium text-zinc-900 hover:text-brand-600">{{ $kw->keyword }}</p>
                            </summary>
                        </details>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $kw->website?->host ?? '—' }} · {{ $kw->device }} · {{ $kw->locale }}</p>
                        @if(($recentRanks[$kw->seo_keyword_id] ?? collect())->isNotEmpty())
                        <details class="mt-1">
                            <summary class="cursor-pointer list-none text-xs text-indigo-600 hover:underline">{{ __('seo.rank_history') }}</summary>
                            <ol class="mt-2 space-y-1.5 rounded-xl border border-zinc-100 bg-zinc-50 p-3">
                                @foreach ($recentRanks[$kw->seo_keyword_id] as $rank)
                                    <li class="flex items-center gap-2 text-xs text-zinc-600">
                                        <span class="inline-block w-28 shrink-0 text-zinc-400">{{ optional($rank->checked_at)->format('m-d H:i') }}</span>
                                        <span class="font-semibold {{ $rank->position === null ? 'text-zinc-400' : ($rank->position <= 10 ? 'text-emerald-600' : 'text-zinc-800') }}">{{ $rank->position ?? __('seo.not_ranked') }}</span>
                                        <span class="text-zinc-300">·</span>
                                        <span class="text-zinc-400">{{ $rank->source === 'auto' ? __('seo.rank_source_auto') : __('seo.rank_source_manual') }}</span>
                                        @if($rank->url_found)
                                            <span class="truncate text-zinc-400">{{ \Illuminate\Support\Str::limit((string) parse_url($rank->url_found, PHP_URL_HOST), 32) }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </details>
                        @endif
                    </td>
                    <td class="px-6 py-3 capitalize text-zinc-600">{{ $kw->search_engine }}</td>
                    <td class="px-6 py-3">
                        <span class="font-semibold {{ $kw->last_position === null ? 'text-zinc-400' : ($kw->last_position <= 10 ? 'text-emerald-600' : 'text-zinc-900') }}">{{ $kw->last_position ?? '—' }}</span>
                        @if ($kw->delta !== null && $kw->delta !== 0)
                            <span class="ml-1 text-xs {{ $kw->delta > 0 ? 'text-emerald-600' : 'text-red-500' }}">{{ $kw->delta > 0 ? '↑' : '↓' }}{{ abs($kw->delta) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-zinc-600">{{ $kw->best_position ?? '—' }}</td>
                    <td class="px-6 py-3 text-xs text-zinc-500">{{ $kw->last_checked_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        @if ($autoEnabled)
                            <form method="POST" action="{{ route('seo.keywords.refresh', $kw->seo_keyword_id) }}" class="inline">
                                @csrf
                                <button class="text-sm text-indigo-600 hover:underline">{{ __('seo.refresh_rank') }}</button>
                            </form>
                        @endif
                        <details class="ml-3 inline-block text-left align-middle">
                            <summary class="cursor-pointer list-none text-sm text-zinc-600 hover:underline">{{ __('seo.manual_snapshot') }}</summary>
                            <form method="POST" action="{{ route('seo.keywords.snapshot', $kw->seo_keyword_id) }}" class="mt-2 flex items-center gap-2">
                                @csrf
                                <input type="number" name="position" min="1" max="1000" placeholder="№" class="w-24 rounded-lg border border-zinc-200 px-2 py-1 text-sm">
                                <button class="rounded-lg bg-zinc-100 px-3 py-1 text-xs text-zinc-700 hover:bg-zinc-200">{{ __('seo.save') }}</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('seo.keywords.destroy', $kw->seo_keyword_id) }}" class="ml-3 inline" onsubmit="return confirm(this.dataset.msg)" data-msg="{{ __('seo.confirm_delete') }}">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-600 hover:underline">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_keywords') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $keywords->links() }}
</div>
@endsection

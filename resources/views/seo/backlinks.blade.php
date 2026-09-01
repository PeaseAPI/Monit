@extends('layouts.app')
@section('title', __('seo.backlinks_title'))
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.backlinks_title') }}</h1>
        <a href="{{ route('seo.backlinks.export') }}" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">CSV</a>
    </div>

    {{-- 汇总卡 --}}
    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">{{ __('seo.backlinks_total') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">{{ __('seo.referring_domains') }}</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ number_format($summary['referring_domains']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">{{ __('seo.active_links') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($summary['active']) }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-4">
            <p class="text-xs text-zinc-500">Dofollow</p>
            <p class="mt-1 text-2xl font-bold text-zinc-600">{{ number_format($summary['dofollow']) }}</p>
        </div>
    </div>

    {{-- 添加表单 --}}
    <form method="POST" action="{{ route('seo.backlinks.store') }}" class="mt-6 rounded-2xl border border-zinc-200 bg-white p-4">
        @csrf
        <div class="grid gap-3 md:grid-cols-5">
            <input type="url" name="source_url" required maxlength="2048" placeholder="{{ __('seo.source_url_placeholder') }}" value="{{ old('source_url') }}"
                   class="md:col-span-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <select name="website_id" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="">{{ __('seo.choose_website') }}</option>
                @foreach ($websites as $site)
                    <option value="{{ $site->website_id }}" @selected(old('website_id') == $site->website_id)>{{ $site->host }}</option>
                @endforeach
            </select>
            <input type="text" name="anchor_text" maxlength="512" placeholder="{{ __('seo.anchor_text') }}" value="{{ old('anchor_text') }}"
                   class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <select name="rel" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="unknown">{{ __('seo.rel_unknown') }}</option>
                <option value="dofollow">Dofollow</option>
                <option value="nofollow">Nofollow</option>
            </select>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <input type="url" name="target_url" maxlength="2048" placeholder="{{ __('seo.target_url_placeholder') }}" value="{{ old('target_url') }}"
                   class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <input type="number" name="dr" min="0" max="100" placeholder="DR" class="w-24 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.add_backlink') }}</button>
        </div>
        @error('source_url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    {{-- 反链表 --}}
    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
            <tr>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.source_url') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.anchor_text') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">Rel</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.link_status') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.last_checked') }}</th>
                <th class="px-6 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse ($links as $link)
                <tr>
                    <td class="max-w-xs px-6 py-3">
                        <a href="{{ $link->source_url }}" target="_blank" rel="noopener nofollow" class="block truncate font-medium text-indigo-600 hover:underline">{{ $link->source_url }}</a>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $link->source_host }}@if($link->dr !== null) · DR {{ $link->dr }}@endif</p>
                    </td>
                    <td class="max-w-[12rem] truncate px-6 py-3 text-zinc-600">{{ $link->anchor_text ?? '—' }}</td>
                    <td class="px-6 py-3 text-zinc-600">{{ $link->rel === 'dofollow' ? 'Dofollow' : ($link->rel === 'nofollow' ? 'Nofollow' : '—') }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ ['active' => 'bg-emerald-50 text-emerald-700', 'lost' => 'bg-red-50 text-red-600', 'pending' => 'bg-zinc-100 text-zinc-500'][$link->status] ?? 'bg-zinc-100 text-zinc-500' }}">{{ __('seo.backlink_status_'.$link->status) }}</span>
                    </td>
                    <td class="px-6 py-3 text-xs text-zinc-500">{{ $link->last_checked_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('seo.backlinks.verify', $link->seo_backlink_id) }}" class="inline">
                            @csrf
                            <button class="text-sm text-indigo-600 hover:underline">{{ __('seo.verify_now') }}</button>
                        </form>
                        <form method="POST" action="{{ route('seo.backlinks.destroy', $link->seo_backlink_id) }}" class="ml-3 inline" onsubmit="return confirm('{{ __('seo.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-600 hover:underline">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_backlinks') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $links->links() }}
</div>
@endsection

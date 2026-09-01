@extends('layouts.app')
@section('title', __('stats.visitors_title'))
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.visitors_title')" />

    {{-- 时间范围 + 导出 --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex rounded-xl border border-zinc-200 bg-white p-1 shadow-sm">
            @foreach ([1 => __('dashboard.today'), 7 => __('dashboard.7days'), 30 => __('dashboard.30days'), 90 => __('stats.90days')] as $r => $label)
                <a href="{{ route('stats.visitors', array_filter(['website' => $website->website_id, 'range' => $r])) }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $range === $r ? 'bg-brand-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('stats.visitors', array_filter(['website' => $website->website_id, 'export' => 'json', 'range' => $range])) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.export_json') }}</a>
            <a href="{{ route('stats.visitors', array_filter(['website' => $website->website_id, 'export' => 'csv', 'range' => $range])) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.export_csv') }}</a>
        </div>
    </div>

    {{-- 访客列表（advanced / lightweight 双模式统一结构） --}}
    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-400">
                <tr>
                    <th class="px-5 py-3 font-medium">{{ __('stats.visitors_id') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.country') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.os') }} / {{ __('stats.browser') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.visitor_pageviews') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.first_source') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.first_seen') }}</th>
                    <th class="px-5 py-3 font-medium">{{ __('stats.last_seen') }}</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($visitors as $v)
                    @php
                        $detailParam = $v['visitor_id'] !== null ? (string) $v['visitor_id'] : $v['visitor_uuid'];
                        $deviceLabel = ['desktop' => __('dashboard.device_desktop'), 'mobile' => __('dashboard.device_mobile'), 'tablet' => __('dashboard.device_tablet')][$v['device_type'] ?? ''] ?? null;
                    @endphp
                    <tr class="transition hover:bg-zinc-50/70">
                        <td class="px-5 py-3 font-mono text-xs text-zinc-500">{{ $v['visitor_id'] !== null ? '#'.$v['visitor_id'] : substr($v['visitor_uuid'], 0, 8).'…' }}</td>
                        <td class="px-5 py-3">
                            @if ($v['country_code'])
                                <span class="whitespace-nowrap">{{ \App\Support\CountryNames::flag($v['country_code']) }} {{ \App\Support\CountryNames::name($v['country_code'], app()->getLocale()) }}</span>
                            @else
                                <span class="text-zinc-400">{{ __('stats.unknown') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-zinc-700">
                            <span class="whitespace-nowrap">{{ $v['os_name'] ?? '—' }} · {{ $v['browser_name'] ?? '—' }}{{ $deviceLabel ? ' · '.$deviceLabel : '' }}</span>
                        </td>
                        <td class="px-5 py-3 font-semibold tabular-nums text-zinc-900">{{ number_format($v['total_events']) }}</td>
                        <td class="px-5 py-3 text-zinc-500">{{ $v['first_referrer'] ?? __('stats.direct_visit') }}</td>
                        <td class="px-5 py-3 whitespace-nowrap tabular-nums text-zinc-500">{{ optional($v['first_date'])->format('m-d H:i') ?? '—' }}</td>
                        <td class="px-5 py-3 whitespace-nowrap tabular-nums text-zinc-500">{{ optional($v['last_date'])->format('m-d H:i') ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('stats.visitor', ['website' => $website->website_id, 'visitorId' => $detailParam]) }}"
                               class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-brand-700 transition hover:bg-brand-50">{{ __('stats.view_journey') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-zinc-400">{{ __('stats.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

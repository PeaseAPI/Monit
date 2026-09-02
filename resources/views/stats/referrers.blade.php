@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.referrers_title')" />
    <x-range-switcher :route-name="'stats.referrers'" :website="$website" :range="$range" />
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.referrer_domain') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.referrer_visits') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.utm_source') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.utm_medium') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.utm_campaign') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($topReferrers ?? [] as $r)
            <tr>
                <td class="px-6 py-3">
                    @if(!empty($r['key']) && $r['key'] !== __('stats.direct_access'))
                        <a href="{{ route('stats.referrer_paths', ['website' => $website->website_id, 'host' => $r['key']]) }}" class="text-brand-600 hover:underline">{{ $r['key'] }}</a>
                    @else
                        {{ $r['key'] ?? __('stats.direct_visit') }}
                    @endif
                </td>
                <td class="px-6 py-3">{{ $r['count'] ?? 0 }}</td>
                <td class="px-6 py-3 text-zinc-500">{{ $r['utm_source'] ?? '-' }}</td>
                <td class="px-6 py-3 text-zinc-500">{{ $r['utm_medium'] ?? '-' }}</td>
                <td class="px-6 py-3 text-zinc-500">{{ $r['utm_campaign'] ?? '-' }}</td>
            </tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('stats.no_data') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection
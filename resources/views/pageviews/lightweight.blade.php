@extends('layouts.app')
@section('title', __('stats.pageviews_lightweight'))
@section('content')
<div class="py-8">
    @include('components.stats-header', ['website' => $website, 'range' => $range ?? 7])

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @include('components.stat-card', ['label' => __('stats.total_pageviews'), 'value' => $overview['pageviews'] ?? 0, 'change' => $overview['pageviews_change'] ?? null])
        @include('components.stat-card', ['label' => __('stats.unique_visitors'), 'value' => $overview['visitors'] ?? 0, 'change' => $overview['visitors_change'] ?? null])
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.pageviews_trend') }}</h2>
        @include('components.bar-chart', ['data' => $series ?? []])
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.top_pages') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.page_path') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.pageviews') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($topPaths ?? [] as $item)
                    <tr><td class="px-6 py-3 font-medium text-zinc-900">{{ $item['path'] }}</td><td class="px-6 py-3 text-zinc-500">{{ $item['count'] }}</td></tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="2">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
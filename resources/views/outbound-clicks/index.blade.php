@extends('layouts.app')
@section('title', __('stats.outbound_clicks'))
@section('content')
<div class="py-8">
    @include('components.stats-header', ['website' => $website, 'range' => $range ?? 7])

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.outbound_clicks') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.target_url') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.clicks') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.last_click') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($clicks ?? [] as $click)
                    <tr>
                        <td class="px-6 py-3 text-brand-600 hover:underline"><a href="{{ $click->url ?? $click->host }}" target="_blank">{{ $click->url ?? $click->host }}</a></td>
                        <td class="px-6 py-3 text-zinc-700 font-medium">{{ $click->count ?? 1 }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $click->datetime }}</td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ ($clicks ?? collect())->links() ?? '' }}
</div>
@endsection
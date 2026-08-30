@extends('layouts.app')
@section('title', __('stats.visitors'))
@section('content')
<div class="py-8">
    @include('components.stats-header', ['website' => $website, 'range' => $range ?? 7])

    <div class="mt-6 grid gap-4 sm:grid-cols-4">
        @include('components.stat-card', ['label' => __('stats.total_visitors'), 'value' => $overview['visitors'] ?? 0])
        @include('components.stat-card', ['label' => __('stats.total_sessions'), 'value' => $overview['sessions'] ?? 0])
        @include('components.stat-card', ['label' => __('stats.avg_duration'), 'value' => ($overview['avg_duration'] ?? '0:00')])
        @include('components.stat-card', ['label' => __('stats.bounce_rate'), 'value' => ($overview['bounce_rate'] ?? 0) . '%'])
    </div>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
        <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('stats.visitor_list') }}</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">ID</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.device') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.os') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.browser') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.country') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.first_visit') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.last_visit') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.sessions') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($visitors ?? [] as $v)
                    <tr class="hover:bg-zinc-50 cursor-pointer" onclick="window.location='{{ route('stats.visitor', [$website, $v['visitor_id'] ?? $v->visitor_id]) }}'">
                        <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $v['visitor_id'] ?? $v->visitor_id }}</td>
                        <td class="px-6 py-3 text-zinc-700">{{ $v['device_type'] ?? $v->device_type ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $v['os_name'] ?? $v->os_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $v['browser_name'] ?? $v->browser_name ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $v['country_code'] ?? $v->country_code ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $v['date'] ?? $v->date ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $v['last_date'] ?? $v->last_date ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-700 font-medium">{{ $v['total_sessions'] ?? $v->total_sessions ?? 0 }}</td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="8">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
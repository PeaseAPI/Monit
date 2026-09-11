@extends('layouts.app')
@section('content')
<div class="mx-auto max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.replays_title')" />
    @php($replayQuota = $website->user?->getPlanSettings()['sessions_replays_limit'] ?? -1)
    @if($replayQuota === 0)
    <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
        <p class="text-sm text-amber-800">{{ __('stats.replays_disabled_quota') }}</p>
        @if((int) auth()->user()?->type === 1)
        <a href="{{ route('admin.users.edit', $website->user?->user_id) }}" class="mt-1 inline-block text-xs font-medium text-amber-900 underline">{{ __('stats.replays_disabled_admin_hint') }}</a>
        @endif
    </div>
    @endif
    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_visitor_id') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.device_type') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.browser') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.os') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.country') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_page') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.replay_time') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($replays ?? [] as $r)
                <tr>
                    <td class="px-6 py-3 font-mono text-xs">{{ $r->visitor_id }}</td>
                    <td class="px-6 py-3 text-sm">{{ ['desktop' => __('dashboard.device_desktop'), 'mobile' => __('dashboard.device_mobile'), 'tablet' => __('dashboard.device_tablet')][$r->visitor?->device_type ?? ''] ?? $r->visitor?->device_type ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ $r->visitor?->browser_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ $r->visitor?->os_name ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm">{{ \App\Support\CountryNames::flag($r->visitor?->country_code) . ' ' . \App\Support\CountryNames::name($r->visitor?->country_code, app()->getLocale()) }}@if($r->visitor?->city_name)<span class="text-zinc-400"> · {{ $r->visitor->city_name }}</span>@endif</td>
                    <td class="px-6 py-3"><a href="{{ route('stats.replays.show', [$website->website_id, $r->replay_id]) }}" class="text-brand-600 hover:underline">{{ __('stats.view_replay') }}</a></td>
                    <td class="px-6 py-3 text-zinc-500">{{ $r->datetime?->format('Y-m-d H:i:s') }}</td>
                </tr>
                @empty
                <tr><td class="px-6 py-8 text-center text-zinc-500" colspan="7">{{ __('stats.no_replays') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($replays) && method_exists($replays, 'links'))
        {{ $replays->links() }}
    @endif
</div>
@endsection
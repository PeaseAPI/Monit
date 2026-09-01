@extends('layouts.app')
@section('title', __('stats.realtime'))
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.realtime')" />
    <p class="mb-6 text-sm text-zinc-500">{{ __('stats.realtime_desc') }}</p>

    <div class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
        <div class="flex items-center justify-center gap-3">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span>
            </span>
            <span id="realtime-count" class="text-6xl font-bold text-zinc-900">{{ $count }}</span>
        </div>
        <p class="mt-3 text-sm text-zinc-500">{{ __('stats.online_visitors') }}</p>
        <p class="mt-1 text-xs text-zinc-400" id="realtime-updated">{{ now()->toDateTimeString() }}</p>
    </div>

    @if(! empty($overview))
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5"><p class="text-sm text-zinc-500">{{ __('stats.pageviews') }}</p><p class="mt-1 text-2xl font-bold text-zinc-900">{{ $overview['pageviews'] ?? 0 }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5"><p class="text-sm text-zinc-500">{{ __('stats.visitors') }}</p><p class="mt-1 text-2xl font-bold text-zinc-900">{{ $overview['visitors'] ?? 0 }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5"><p class="text-sm text-zinc-500">{{ __('stats.sessions') }}</p><p class="mt-1 text-2xl font-bold text-zinc-900">{{ $overview['sessions'] ?? 0 }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-5"><p class="text-sm text-zinc-500">{{ __('stats.bounce_rate') }}</p><p class="mt-1 text-2xl font-bold text-zinc-900">{{ $overview['bounce_rate'] ?? '0%' }}</p></div>
    </div>
    @endif
</div>

<script>
    (function () {
        var url = {{ json_encode(route('stats.realtime.data', $website->website_id)) }};
        function tick() {
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('realtime-count').textContent = data.count;
                    document.getElementById('realtime-updated').textContent = data.updated_at;
                })
                .catch(function () {});
        }
        setInterval(tick, 5000);
    })();
</script>
@endsection
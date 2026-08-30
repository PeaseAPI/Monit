@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900">{{ $website->name }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                {{ $website->host ?? $website->domain }}
                <span class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-600">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                    {{ $realtime }} {{ __('stats.realtime_online') }}
                </span>
            </p>
        </div>
        <x-range-switcher :route-name="'stats.index'" :website="$website" :range="$range" class="mb-0" />
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ route('stats.visitors', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.visitors') }}</a>
        <a href="{{ route('stats.behavior', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.behavior') }}</a>
        <a href="{{ route('stats.top_cities', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_cities') }}</a>
        <a href="{{ route('stats.top_languages', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_languages') }}</a>
        <a href="{{ route('stats.top_resolutions', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_resolutions') }}</a>
        <a href="{{ route('stats.referrers', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.referrers') }}</a>
        <a href="{{ route('stats.referral_categories', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.referral_categories') }}</a>
        <a href="{{ route('stats.top_continents', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_continents') }}</a>
        <a href="{{ route('stats.top_timezones', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_timezones') }}</a>
        <a href="{{ route('stats.top_themes', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.top_themes') }}</a>
        <a href="{{ route('goals.index', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.goals') }}</a>
        <a href="{{ route('stats.outbound-clicks', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.outbound_clicks') }}</a>
        <a href="{{ route('annotations.index', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.annotations') }}</a>
        <a href="{{ route('heatmaps.index', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.heatmaps') }}</a>
        <a href="{{ route('replays.index', $website->website_id) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.nav.replays') }}</a>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card :label="__('stats.pageviews')" :value="number_format($overview['pageviews'])" :hint="__('stats.pageviews_total')" />
        <x-stat-card :label="__('stats.visitors')" :value="number_format($overview['visitors'])" :hint="__('stats.visitors_unique')" />
        <x-stat-card :label="__('stats.sessions')" :value="number_format($overview['sessions'])" :hint="__('stats.sessions_total')" />
        <x-stat-card :label="__('stats.bounce_rate')" :value="$overview['bounce_rate'].'%'" :hint="__('stats.bounce_rate_hint')" />
        <x-stat-card :label="__('stats.avg_duration')" :value="$overview['avg_duration'] > 0 ? gmdate('i:s', $overview['avg_duration']) : '-'" :hint="__('stats.avg_duration_hint')" />
    </div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.pageviews_trend') }}</h3>
        <x-bar-chart :series="$series" />
    </div>
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-rank-panel :title="__('stats.top_pages')" :items="$topPaths" />
        <x-rank-panel :title="__('stats.top_referrers')" :items="$topReferrers" />
        <x-rank-panel :title="__('stats.top_countries')" :items="$topCountries" />
        <x-rank-panel :title="__('stats.top_devices')" :items="$topDevices" />
        <x-rank-panel :title="__('stats.top_browsers')" :items="$topBrowsers" />
        <x-rank-panel :title="__('stats.top_os')" :items="$topOs" />
    </div>

    {{-- AI 数据洞察（规格书 §12.6）：后台启用后展示 --}}
    @if(\App\Services\Ai\AiService::insightsEnabled())
    <div class="mt-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 to-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-brand-700">✨ {{ __('stats.ai_insight') }}</h3>
                <p class="mt-1 text-xs text-zinc-400">{{ __('stats.ai_insight_desc') }}</p>
            </div>
            <button type="button" id="ai-insight-btn" data-url="{{ route('stats.ai_insight', $website->website_id) }}" data-range="{{ $range }}"
                class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">
                {{ __('stats.ai_insight_generate') }}
            </button>
        </div>
        <div id="ai-insight-result" class="mt-4 hidden whitespace-pre-wrap rounded-xl border border-brand-100 bg-white p-4 text-sm leading-relaxed text-zinc-700"></div>
    </div>
    <script>
        document.getElementById('ai-insight-btn')?.addEventListener('click', async function () {
            const btn = this, box = document.getElementById('ai-insight-result');
            btn.disabled = true;
            btn.textContent = "{{ __('stats.ai_insight_loading') }}";
            box.classList.add('hidden');
            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                    body: JSON.stringify({ range: btn.dataset.range }),
                });
                const data = await res.json();
                if (! res.ok) { box.textContent = data.error === 'ai_disabled' ? "{{ __('stats.ai_insight_disabled') }}" : "{{ __('stats.ai_insight_failed') }}"; }
                else { box.textContent = data.insight; }
            } catch (e) {
                box.textContent = "{{ __('stats.ai_insight_failed') }}";
            } finally {
                box.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = "{{ __('stats.ai_insight_generate') }}";
            }
        });
    </script>
    @endif
</div>
@endsection

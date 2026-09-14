@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-zinc-900">{{ $website->name }}</h2>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                {{ $website->host ?? $website->domain }}
                <a href="{{ route('stats.realtime', $website->website_id) }}"
                   class="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-600 transition hover:bg-emerald-100 hover:text-emerald-700">
                    <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span></span>
                    {{ $realtime }} {{ __('stats.realtime_online') }}
                </a>
            </p>
        </div>
        <x-range-switcher :route-name="'stats.index'" :website="$website" :range="$range" class="mb-0" />
    </div>
    {{-- 51.la 对标：分组页签导航（高频入口平铺，细分维度收进「更多」下拉） --}}
    <nav class="mt-5 flex items-center gap-0.5 overflow-x-auto border-b border-zinc-200" aria-label="Stats sections">
        <span aria-current="page" class="-mb-px whitespace-nowrap border-b-2 border-brand-600 px-3.5 py-2.5 text-sm font-semibold text-brand-600">{{ __('stats.nav.overview') }}</span>
        <a href="{{ route('stats.visitors', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.visitors') }}</a>
        <a href="{{ route('stats.behavior', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.behavior') }}</a>
        <a href="{{ route('stats.referrers', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.referrers') }}</a>
        <a href="{{ route('stats.goals', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.goals') }}</a>
        <a href="{{ route('stats.heatmaps', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.heatmaps') }}</a>
        <a href="{{ route('stats.replays', $website->website_id) }}" class="-mb-px whitespace-nowrap border-b-2 border-transparent px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:border-zinc-300 hover:text-zinc-900">{{ __('stats.nav.replays') }}</a>
        <details class="relative">
            <summary class="flex cursor-pointer list-none items-center gap-1 whitespace-nowrap px-3.5 py-2.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-900 [&::-webkit-details-marker]:hidden">
                {{ __('stats.nav.more') }}
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-52 rounded-xl border border-zinc-200 bg-white p-1.5 shadow-lg">
                @foreach ([
                    'stats.top_cities' => __('stats.nav.top_cities'),
                    'stats.top_languages' => __('stats.nav.top_languages'),
                    'stats.top_resolutions' => __('stats.nav.top_resolutions'),
                    'stats.top_continents' => __('stats.nav.top_continents'),
                    'stats.top_timezones' => __('stats.nav.top_timezones'),
                    'stats.top_themes' => __('stats.nav.top_themes'),
                    'stats.referral_categories' => __('stats.nav.referral_categories'),
                    'stats.outbound-clicks' => __('stats.nav.outbound_clicks'),
                    'stats.annotations' => __('stats.nav.annotations'),
                ] as $r => $label)
                    <a href="{{ route($r, $website->website_id) }}" class="block rounded-lg px-3 py-2 text-sm text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900">{{ $label }}</a>
                @endforeach
            </div>
        </details>
    </nav>
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-stat-card :label="__('stats.pageviews')" :value="number_format($overview['pageviews'])" :hint="__('stats.pageviews_total')" />
        <x-stat-card :label="__('stats.visitors')" :value="number_format($overview['visitors'])" :hint="__('stats.visitors_unique')" />
        <x-stat-card :label="__('stats.sessions')" :value="number_format($overview['sessions'])" :hint="__('stats.sessions_total')" />
        <x-stat-card :label="__('stats.bounce_rate')" :value="$overview['bounce_rate'].'%'" :hint="__('stats.bounce_rate_hint')" />
        <x-stat-card :label="__('stats.avg_duration')" :value="$overview['avg_duration'] > 0 ? gmdate('i:s', $overview['avg_duration']) : '-'" :hint="__('stats.avg_duration_hint')" />
    </div>
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-zinc-700">{{ __('stats.pageviews_trend') }}</h3>
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-gradient-to-t from-brand-600 to-brand-400"></span>{{ __('stats.pageviews') }}</span>
            </div>
        </div>
        <x-bar-chart :series="$series" />
    </div>
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-rank-panel :title="__('stats.top_pages')" :items="$topPaths" :showRank="true" />
        <x-rank-panel :title="__('stats.top_referrers')" :items="$topReferrers" :showRank="true" />
        <x-rank-panel :title="__('stats.top_countries')" :items="$topCountries" :showRank="true" />
        <x-rank-panel :title="__('stats.top_devices')" :items="$topDevices" :showRank="true" />
        <x-rank-panel :title="__('stats.top_browsers')" :items="$topBrowsers" :showRank="true" />
        <x-rank-panel :title="__('stats.top_os')" :items="$topOs" :showRank="true" />
    </div>

    {{-- AI 数据洞察（规格书 §12.6）：后台启用后展示 --}}
    @if(\App\Services\Ai\AiService::insightsEnabled())
    <div class="mt-6 rounded-2xl border border-brand-100 bg-gradient-to-br from-brand-50 to-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-brand-700">✨ {{ __('stats.ai_insight') }}</h3>
                <p class="mt-1 text-xs text-zinc-400">{{ __('stats.ai_insight_desc') }}</p>
            </div>
            <button type="button" id="ai-insight-btn" data-url="{{ route_path('stats.ai_insight', $website->website_id) }}" data-range="{{ $range }}"
                data-text-idle="{{ __('stats.ai_insight_generate') }}" data-text-loading="{{ __('stats.ai_insight_loading') }}"
                data-text-failed="{{ __('stats.ai_insight_failed') }}" data-text-disabled="{{ __('stats.ai_insight_disabled') }}"
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
            btn.textContent = btn.dataset.textLoading;
            box.classList.add('hidden');
            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                    body: JSON.stringify({ range: btn.dataset.range }),
                });
                const data = await res.json();
                if (! res.ok) { box.textContent = data.error === 'ai_disabled' ? btn.dataset.textDisabled : btn.dataset.textFailed; }
                else { box.textContent = data.insight; }
            } catch (e) {
                box.textContent = btn.dataset.textFailed;
            } finally {
                box.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = btn.dataset.textIdle;
            }
        });
    </script>
    @endif
</div>
@endsection

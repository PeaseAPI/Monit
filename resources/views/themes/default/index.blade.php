{{-- ============================================================
  Monit 默认落地页主题（M23 模板机制 · 规格 §15）
  - 主题切换：后台 设置 → 品牌 → 落地页主题（branding.landing_theme）
  - 新增主题：复制本目录为 themes/{your-theme}/，修改后台设置即可启用
  - 品牌元素（logo/主色/ICP/页脚代码）由 App\Support\Brand 从设置读取
  - 文案：语言包 landing.*；Hero 标题/副标题可被后台 branding 覆盖
============================================================ --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Support\Brand::heroTitle() ?? __('landing.title') }} · {{ \App\Support\Brand::name() }}</title>
    <meta name="description" content="{{ __('landing.subtitle') }}">
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-zinc-900 antialiased">

    @include('parts.announcement_bar')

    {{{-- ===== 顶部导航 ===== --}}}
    <header class="sticky top-0 z-40 border-b border-zinc-100 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <x-brand-logo href="{{ route('index') }}" />

            <nav class="hidden items-center gap-8 md:flex">
                <a href="#features" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_features') }}</a>
                <a href="#showcase" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_showcase') }}</a>
                <a href="#pricing" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_pricing') }}</a>
                @if (\App\Support\Settings::get('seo.tools_is_enabled', true)
                    && (auth()->check() || in_array(\App\Support\Settings::get('seo.tools_guest_access'), [true, 'true', '1'], true)))
                <a href="{{ route('seo.tools') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_seo_tools') }}</a>
                @endif
                @if (\App\Support\Settings::get('seo.audits_is_enabled', true))
                <a href="{{ route('seo.directory') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_seo_directory') }}</a>
                @endif
                <a href="{{ route('blog') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_blog') }}</a>
                <a href="{{ route('help') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_help') }}</a>
            </nav>

            <div class="flex items-center gap-3">
                {{-- 语言切换器（原站 🇨🇳/🇺🇸 下拉，/locale/{code} 切换 session；原生 details 实现，无 JS 依赖） --}}
                @if (count((array) config('monit.locales')) > 1)
                <details class="group relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-zinc-200 px-3 py-2 text-sm text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 [&::-webkit-details-marker]:hidden">
                        <span>{{ config('monit.locales.'.app()->getLocale().'.flag', '🌐') }}</span>
                        <span class="hidden sm:inline">{{ config('monit.locales.'.app()->getLocale().'.label', app()->getLocale()) }}</span>
                        <svg class="h-3.5 w-3.5 text-zinc-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-44 overflow-hidden rounded-xl border border-zinc-100 bg-white py-1 shadow-lg shadow-zinc-900/5">
                        @foreach (config('monit.locales') as $code => $meta)
                        <a href="{{ route('locale.switch', $code) }}"
                            class="flex items-center justify-between px-4 py-2 text-sm {{ $code === app()->getLocale() ? 'bg-brand-50 font-medium text-brand-700' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}">
                            <span>{{ $meta['flag'] }} {{ $meta['label'] }}</span>
                            @if ($code === app()->getLocale())
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </details>
                @endif

                <a href="{{ route('login') }}" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:block">{{ __('landing.nav_login') }}</a>
                <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">
                    {{ __('landing.nav_get_started') }}
                </a>
            </div>
        </div>
    </header>

    {{-- ===== Hero ===== --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-48 left-1/2 h-[560px] w-[900px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-brand-200/60 via-brand-100/40 to-transparent blur-3xl"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-brand-300/60 to-transparent"></div>
        </div>

        <div class="mx-auto max-w-7xl px-6 pb-20 pt-24 text-center md:pt-32">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-4 py-1.5 text-xs font-medium text-brand-700">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-500 opacity-60"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-600"></span>
                </span>
                {{ __('landing.hero_badge') }}
            </div>

            <h1 class="mx-auto max-w-4xl text-4xl leading-tight font-bold tracking-tight text-zinc-900 md:text-6xl md:leading-[1.1]">
                {{ \App\Support\Brand::heroTitle() ?? __('landing.hero_title') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-zinc-500 md:text-xl">
                {{ \App\Support\Brand::heroSubtitle() ?? __('landing.hero_subtitle') }}
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="w-full rounded-2xl bg-gradient-to-r from-brand-600 to-brand-700 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-brand-600/30 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/40 sm:w-auto">
                    {{ __('landing.cta_primary') }} →
                </a>
                <a href="#showcase" class="w-full rounded-2xl border border-zinc-200 bg-white px-8 py-4 text-base font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 sm:w-auto">
                    {{ __('landing.cta_secondary') }}
                </a>
            </div>
            <p class="mt-4 text-sm text-zinc-400">{{ __('landing.no_card_required') }}</p>

            {{-- 免费 SEO 分析（对标 monit.cn 首页获客组件：POST /seo/analyze，审计开关控制） --}}
            @if (\App\Support\Settings::get('seo.audits_is_enabled', true))
            <div class="mx-auto mt-12 max-w-xl">
                <form method="POST" action="{{ route('seo.analyze') }}" class="flex flex-col gap-2 rounded-2xl border border-zinc-200 bg-white p-2 shadow-lg shadow-zinc-900/5 sm:flex-row">
                    @csrf
                    <input type="url" name="url" required placeholder="{{ __('landing.seo_analyze_placeholder') }}" value="{{ old('url') }}"
                           class="flex-1 rounded-xl border-0 px-4 py-3 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <button type="submit" class="rounded-xl bg-zinc-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700">
                        {{ __('landing.seo_analyze_cta') }}
                    </button>
                </form>
                <p class="mt-2 text-xs text-zinc-400">{{ __('landing.seo_analyze_desc') }}</p>
                @error('url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @endif

            {{-- 平台统计徽章（原站 hero 下方 "9 websites / 44K pageviews"，实时聚合 1 分钟缓存） --}}
            @if (($stats['websites'] ?? 0) > 0)
            <dl class="mx-auto mt-10 grid max-w-2xl grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-zinc-100 bg-white/70 px-4 py-3 backdrop-blur">
                    <dt class="text-xs text-zinc-400">{{ __('landing.stats_websites') }}</dt>
                    <dd class="mt-0.5 text-xl font-bold tabular-nums text-zinc-900">{{ number_format($stats['websites']) }}+</dd>
                </div>
                <div class="rounded-2xl border border-zinc-100 bg-white/70 px-4 py-3 backdrop-blur">
                    <dt class="text-xs text-zinc-400">{{ __('landing.stats_pageviews') }}</dt>
                    <dd class="mt-0.5 text-xl font-bold tabular-nums text-zinc-900">{{ $stats['pageviews'] >= 1000 ? round($stats['pageviews'] / 1000).'K+' : number_format($stats['pageviews']) }}</dd>
                </div>
                <div class="rounded-2xl border border-zinc-100 bg-white/70 px-4 py-3 backdrop-blur">
                    <dt class="text-xs text-zinc-400">{{ __('landing.stats_retention') }}</dt>
                    <dd class="mt-0.5 text-xl font-bold tabular-nums text-zinc-900">365{{ __('landing.stats_days') }}</dd>
                </div>
                <div class="rounded-2xl border border-zinc-100 bg-white/70 px-4 py-3 backdrop-blur">
                    <dt class="text-xs text-zinc-400">{{ __('landing.stats_uptime') }}</dt>
                    <dd class="mt-0.5 text-xl font-bold tabular-nums text-zinc-900">99.9%</dd>
                </div>
            </dl>
            @endif

            {{-- 产品界面模拟（纯 CSS，无外部资源） --}}
            <div class="relative mx-auto mt-16 max-w-5xl pb-10 [perspective:1600px]">
                <div class="pointer-events-none absolute -inset-x-10 -top-10 bottom-2 rounded-[2.5rem] bg-gradient-to-tr from-brand-200/50 via-brand-100/30 to-transparent blur-2xl"></div>
                <div class="animate-float relative">
                <div class="rounded-2xl border border-zinc-200 bg-white shadow-2xl shadow-zinc-900/10 [transform:rotateX(5deg)] transition-transform duration-700 hover:[transform:rotateX(0deg)]">
                    <div class="flex items-center gap-2 border-b border-zinc-100 px-4 py-3">
                        <span class="h-3 w-3 rounded-full bg-red-400"></span>
                        <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                        <span class="h-3 w-3 rounded-full bg-green-400"></span>
                        <span class="ml-4 rounded-md bg-zinc-100 px-3 py-1 text-xs text-zinc-400">{{ \App\Support\Brand::name() }} · {{ __('landing.mock_dashboard') }}</span>
                    </div>
                    <div class="grid gap-4 p-6 text-left md:grid-cols-4">
                        @foreach ([
                            ['value' => '12,847', 'label' => __('landing.mock_pageviews'), 'trend' => '+18.2%'],
                            ['value' => '3,204', 'label' => __('landing.mock_visitors'), 'trend' => '+12.6%'],
                            ['value' => '02:47', 'label' => __('landing.mock_duration'), 'trend' => '+4.1%'],
                            ['value' => '38.5%', 'label' => __('landing.mock_bounce'), 'trend' => '-2.3%'],
                        ] as $stat)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/60 p-4">
                            <p class="text-2xl font-bold text-zinc-900">{{ $stat['value'] }}</p>
                            <p class="mt-1 flex items-center justify-between text-xs">
                                <span class="text-zinc-500">{{ $stat['label'] }}</span>
                                <span class="font-medium text-emerald-600">{{ $stat['trend'] }}</span>
                            </p>
                        </div>
                        @endforeach
                        <div class="relative col-span-full h-44 overflow-hidden rounded-xl border border-zinc-100 bg-gradient-to-b from-brand-50/50 to-white p-4">
                            <div class="flex h-full items-end gap-2">
                                @foreach ([35, 48, 42, 60, 55, 72, 66, 80, 75, 90, 86, 100] as $h)
                                <div class="flex-1 rounded-t-md bg-gradient-to-t from-brand-600/80 to-brand-400/80" style="height: {{ $h }}%"></div>
                                @endforeach
                            </div>
                            <div class="absolute inset-x-4 top-1/2 border-t border-dashed border-brand-300/60"></div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== 功能矩阵 ===== --}}
    <section id="features" class="border-t border-zinc-100 bg-zinc-50/50 py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">{{ __('landing.features_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.features_title') }}</h2>
                <p class="mt-4 text-lg text-zinc-500">{{ __('landing.features_subtitle') }}</p>
            </div>

            <div class="reveal mt-16 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['icon' => 'chart', 'title' => __('landing.feature_realtime_title'), 'desc' => __('landing.feature_realtime_desc')],
                    ['icon' => 'replay', 'title' => __('landing.feature_replay_title'), 'desc' => __('landing.feature_replay_desc')],
                    ['icon' => 'heat', 'title' => __('landing.feature_heatmap_title'), 'desc' => __('landing.feature_heatmap_desc')],
                    ['icon' => 'goal', 'title' => __('landing.feature_goals_title'), 'desc' => __('landing.feature_goals_desc')],
                    ['icon' => 'shield', 'title' => __('landing.feature_privacy_title'), 'desc' => __('landing.feature_privacy_desc')],
                    ['icon' => 'api', 'title' => __('landing.feature_api_title'), 'desc' => __('landing.feature_api_desc')],
                ] as $feature)
                <div class="group rounded-2xl border border-zinc-200 bg-white p-8 transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-600/5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100 transition group-hover:bg-brand-600 group-hover:text-white">
                        @if ($feature['icon'] === 'chart')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 8.5 8l4 4 8-8M21 4v6m0-6h-6"/></svg>
                        @elseif ($feature['icon'] === 'replay')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-3-6.7M21 3v5h-5"/></svg>
                        @elseif ($feature['icon'] === 'heat')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 4v16M12 8v12M18 5v15"/></svg>
                        @elseif ($feature['icon'] === 'goal')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>
                        @elseif ($feature['icon'] === 'shield')
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v6c0 4.5-3.5 8-8 9-4.5-1-8-4.5-8-9V6l8-3z"/></svg>
                        @else
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8l-4 4 4 4M16 8l4 4-4 4M13 5l-2 14"/></svg>
                        @endif
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-zinc-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ $feature['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>


    {{-- ===== 亮点（深色区块） ===== --}}
    <section id="showcase" class="relative overflow-hidden bg-zinc-950 py-24">
        <div class="pointer-events-none absolute -top-40 right-0 h-[420px] w-[420px] rounded-full bg-brand-600/20 blur-[120px]"></div>
        <div class="pointer-events-none absolute -bottom-40 left-0 h-[420px] w-[420px] rounded-full bg-brand-800/30 blur-[120px]"></div>

        <div class="relative mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white md:text-4xl">{{ __('landing.why_title') }}</h2>
                <p class="mt-4 text-lg text-zinc-400">{{ __('landing.why_subtitle') }}</p>
            </div>

            <div class="reveal mt-16 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['stat' => '< 1 KB', 'title' => __('landing.why_pixel_title'), 'desc' => __('landing.why_pixel_desc')],
                    ['stat' => '100%', 'title' => __('landing.why_data_title'), 'desc' => __('landing.why_data_desc')],
                    ['stat' => 'GDPR', 'title' => __('landing.why_gdpr_title'), 'desc' => __('landing.why_gdpr_desc')],
                ] as $item)
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8">
                    <p class="bg-gradient-to-r from-brand-300 to-brand-500 bg-clip-text text-4xl font-bold text-transparent">{{ $item['stat'] }}</p>
                    <h3 class="mt-4 text-lg font-semibold text-white">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $item['desc'] }}</p>
                </div>
                @endforeach
            </div>

            {{-- 三步上手 --}}
            <div class="reveal mt-20 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['step' => '01', 'title' => __('landing.step1_title'), 'desc' => __('landing.step1_desc')],
                    ['step' => '02', 'title' => __('landing.step2_title'), 'desc' => __('landing.step2_desc')],
                    ['step' => '03', 'title' => __('landing.step3_title'), 'desc' => __('landing.step3_desc')],
                ] as $step)
                <div class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-8">
                    <p class="font-mono text-sm font-semibold text-brand-400">{{ $step['step'] }}</p>
                    <h3 class="mt-3 font-semibold text-white">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-400">{{ $step['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>


    {{-- ===== 定价（后台 设置→主要→首页区块 可关闭 + Brand::showLandingPlans） ===== --}}
    @php($showPlans = filter_var(\App\Support\Settings::get('main.display_index_plans', true), FILTER_VALIDATE_BOOLEAN))
    @if ($showPlans && \App\Support\Brand::showLandingPlans())
    <section id="pricing" class="py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.pricing_title') }}</h2>
                <p class="mt-4 text-lg text-zinc-500">{{ __('landing.pricing_subtitle') }}</p>
                @if (count($currencies ?? []) > 1)
                <form method="GET" action="{{ route('index') }}" class="mt-6 inline-flex items-center gap-2" id="landing-currency-form">
                    <label for="landing-currency" class="text-sm text-zinc-500">{{ __('landing.currency') }}</label>
                    <select id="landing-currency" name="currency" onchange="this.form.submit()"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-700">
                        @foreach ($currencies as $code => $meta)
                            <option value="{{ $code }}" @selected($code === ($currency ?? 'CNY'))>{{ $code }} {{ $meta['symbol'] ?? '' }}</option>
                        @endforeach
                    </select>
                </form>
                @endif

                {{-- 计费周期切换（原站 Monthly / Annual toggle）：纯 JS 切换 [data-price] 显隐 --}}
                <div id="billing-toggle" class="mt-8 inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-zinc-50 p-1">
                    <button type="button" data-freq="monthly" aria-pressed="true"
                        class="rounded-full bg-white px-5 py-1.5 text-sm font-semibold text-zinc-900 shadow-sm">{{ __('landing.billing_monthly') }}</button>
                    <button type="button" data-freq="annual" aria-pressed="false"
                        class="rounded-full px-5 py-1.5 text-sm font-medium text-zinc-500 transition hover:text-zinc-900">
                        {{ __('landing.billing_annual') }}
                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">{{ __('landing.save_badge') }}</span>
                    </button>
                </div>
            </div>

            <div class="reveal mt-16 grid gap-6 md:grid-cols-3">
                @forelse ($plans ?? [] as $plan)
                @php($planCode = $plan->landing_currency ?? ($currency ?? 'CNY'))
                @php($symbol = $currencies[$planCode]['symbol'] ?? '')
                @php($featured = ($loop->count >= 3) && ($loop->middle ?? false))
                @php($s = $plan->settings ?? [])
                @php($annual = $plan->landing_price_annual)
                @php($savePct = ($annual && $plan->landing_price > 0) ? max(0, (int) round((1 - $annual / ($plan->landing_price * 12)) * 100)) : 0)
                <div class="{{ $featured ? 'relative rounded-2xl border-2 border-brand-600 bg-white p-8 shadow-xl shadow-brand-600/10 md:-translate-y-3' : 'rounded-2xl border border-zinc-200 bg-white p-8' }}">
                    @if ($featured)
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">{{ __('landing.popular') }}</span>
                    @endif
                    <h3 class="text-lg font-semibold text-zinc-900">{{ $plan->name }}</h3>
                    <p class="mt-3">
                        @if ($plan->landing_price !== null && (float) $plan->landing_price > 0)
                            <span data-price="monthly" class="text-4xl font-bold text-zinc-900">{{ \App\Support\Currency::format((float) $plan->landing_price, $planCode) }}</span>
                            <span data-price="monthly" class="text-sm font-normal text-zinc-400">/{{ __('landing.per_month') }}</span>
                            @if ($annual !== null && (float) $annual > 0)
                            <span data-price="annual" class="hidden text-4xl font-bold text-zinc-900">{{ \App\Support\Currency::format((float) $annual, $planCode) }}</span>
                            <span data-price="annual" class="hidden text-sm font-normal text-zinc-400">/{{ __('landing.per_year') }}</span>
                            @if ($savePct > 0)
                            <span data-price="annual" class="hidden ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">{{ __('landing.save_percent', ['percent' => $savePct]) }}</span>
                            @endif
                            @endif
                        @else
                            <span class="text-4xl font-bold text-zinc-900">{{ __('landing.free') }}</span>
                        @endif
                    </p>
                    @if ($plan->trial_days > 0)
                    <p class="mt-2 text-xs font-medium text-brand-600">{{ __('landing.trial_days_note', ['days' => $plan->trial_days]) }}</p>
                    @endif
                    <p class="mt-3 text-sm leading-relaxed text-zinc-500">{{ $plan->description }}</p>

                    @if (!empty($s))
                    <ul class="mt-5 space-y-2.5 border-t border-zinc-100 pt-5 text-sm text-zinc-600">
                        <li>{{ ($s['websites_limit'] ?? 0) === -1 ? __('landing.feat_websites_unlimited') : __('landing.feat_websites', ['count' => $s['websites_limit'] ?? 1]) }}</li>
                        <li>{{ ($s['sessions_replays_limit'] ?? 0) === 0 ? __('landing.feat_no_replays') : __('landing.feat_replays', ['days' => $s['sessions_replays_retention'] ?? 30]) }}</li>
                        <li>{{ __('landing.feat_retention', ['days' => $s['events_children_retention'] ?? 90]) }}</li>
                        @if (!empty($s['teams_is_enabled']))<li>{{ __('landing.feat_teams') }}</li>@endif
                        @if (!empty($s['api_is_enabled']))<li>{{ __('landing.feat_api') }}</li>@endif
                        @if (!empty($s['email_reports_is_enabled']))<li>{{ __('landing.feat_email_reports') }}</li>@endif
                    </ul>
                    @endif

                    <a href="{{ route('register') }}" class="{{ $featured ? 'mt-6 block rounded-xl bg-brand-600 px-6 py-3 text-center text-sm font-semibold text-white transition hover:bg-brand-700' : 'mt-6 block rounded-xl border border-zinc-300 px-6 py-3 text-center text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50' }}">
                        {{ __('landing.get_started') }}
                    </a>
                </div>
                @empty
                <p class="col-span-full text-center text-zinc-500">{{ __('common.no_plans') }}</p>
                @endforelse
            </div>
            <script>
                (function () {
                    var toggle = document.getElementById('billing-toggle');
                    if (!toggle) return;
                    var buttons = toggle.querySelectorAll('[data-freq]');
                    buttons.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var freq = btn.dataset.freq;
                            buttons.forEach(function (b) {
                                var on = b === btn;
                                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                                b.classList.toggle('bg-white', on);
                                b.classList.toggle('shadow-sm', on);
                                b.classList.toggle('font-semibold', on);
                                b.classList.toggle('text-zinc-900', on);
                                b.classList.toggle('text-zinc-500', !on);
                            });
                            document.querySelectorAll('[data-price]').forEach(function (el) {
                                el.classList.toggle('hidden', el.dataset.price !== freq);
                            });
                        });
                    });
                })();
            </script>
        </div>
    </section>
    @endif

    {{-- ===== 用户评价（原站 index testimonials 区 · 后台可关闭） ===== --}}
    @php($showTestimonials = filter_var(\App\Support\Settings::get('main.display_index_testimonials', true), FILTER_VALIDATE_BOOLEAN))
    @if ($showTestimonials)
    <section class="relative overflow-hidden border-t border-zinc-100 bg-zinc-50/50 py-20 md:py-24">
        <div class="pointer-events-none absolute -top-24 right-0 h-72 w-72 rounded-full bg-brand-100/60 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-0 h-72 w-72 rounded-full bg-brand-50 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">{{ __('landing.testimonials_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.testimonials_title') }}</h2>
                <p class="mt-4 text-base text-zinc-500">{{ __('landing.testimonials_subtitle') }}</p>
            </div>
            </div>
            {{-- 信任徽章条 --}}
            <div class="reveal mt-14 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['key' => 'landing.trust_data_own', 'path' => 'M4.5 12h15m-15 0a1.5 1.5 0 0 1-1.5-1.5v-3A1.5 1.5 0 0 1 4.5 6h15a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5m-15 0a1.5 1.5 0 0 0-1.5 1.5v3a1.5 1.5 0 0 0 1.5 1.5h15a1.5 1.5 0 0 0 1.5-1.5v-3a1.5 1.5 0 0 0-1.5-1.5M9 9h.01M9 15h.01'],
                    ['key' => 'landing.trust_privacy', 'path' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.96 11.96 0 0 1 3.598 6 12 12 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286Z'],
                    ['key' => 'landing.trust_support', 'path' => 'M2.25 13.5h3.75a.75.75 0 0 0 .75-.75V9a.75.75 0 0 0-.75-.75H3.375a.375.375 0 0 0-.375.375v4.875c0 .207.168.375.375.375Zm0 0v3.375c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V13.5m-12 0v-3.375m12 3.375h3.75a.375.375 0 0 0 .375-.375V9a.75.75 0 0 0-.75-.75h-2.625a.375.375 0 0 0-.375.375V13.5Z'],
                ] as $badge)
                <div class="flex items-center gap-3 rounded-2xl border border-zinc-200/70 bg-white/70 px-5 py-4 backdrop-blur transition hover:border-brand-200">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $badge['path'] }}"/></svg></span>
                    <p class="text-sm font-medium text-zinc-700">{{ __($badge['key']) }}</p>
                </div>
                @endforeach
            </div>

            {{-- 用户评价卡片 --}}
            <div class="reveal mt-10 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['quote' => 'landing.testimonial_1_quote', 'author' => 'landing.testimonial_1_author', 'role' => 'landing.testimonial_1_role'],
                    ['quote' => 'landing.testimonial_2_quote', 'author' => 'landing.testimonial_2_author', 'role' => 'landing.testimonial_2_role'],
                    ['quote' => 'landing.testimonial_3_quote', 'author' => 'landing.testimonial_3_author', 'role' => 'landing.testimonial_3_role'],
                ] as $testimonial)
                <figure class="flex h-full flex-col justify-between rounded-2xl border border-zinc-200/70 bg-white/70 p-7 backdrop-blur transition hover:border-brand-200">
                    <blockquote class="text-sm leading-relaxed text-zinc-600">“{{ __($testimonial['quote']) }}”</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-indigo-500 text-sm font-bold text-white">{{ mb_substr(__($testimonial['author']), 0, 1) }}</span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-900">{{ __($testimonial['author']) }}</p>
                            <p class="text-xs text-zinc-400">{{ __($testimonial['role']) }}</p>
                        </div>
                    </figcaption>
                </figure>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ===== FAQ（后台 设置→主要→首页区块 可关闭） ===== --}}
    @php($showFaq = filter_var(\App\Support\Settings::get('main.display_index_faq', true), FILTER_VALIDATE_BOOLEAN))
    @if ($showFaq)
    <section class="border-t border-zinc-100 bg-white py-20 md:py-24">
        <div class="mx-auto grid max-w-6xl gap-12 px-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div class="lg:sticky lg:top-24 lg:self-start">
                <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900">{{ __('landing.faq_title') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-zinc-500">{{ __('landing.subtitle') }}</p>
                <a href="{{ route('contact') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-brand-500 hover:text-brand-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                    {{ __('landing.faq_contact_btn') }}
                </a>
                <p class="mt-3 text-xs text-zinc-400">{{ __('landing.faq_still_questions') }}</p>
            </div>
            <div class="space-y-3.5">
                @foreach ([
                    __('landing.faq_q1') => __('landing.faq_a1'),
                    __('landing.faq_q2') => __('landing.faq_a2'),
                    __('landing.faq_q3') => __('landing.faq_a3'),
                    __('landing.faq_q4') => __('landing.faq_a4'),
                    __('landing.faq_q5') => __('landing.faq_a5'),
                ] as $q => $a)
                <details class="group rounded-2xl border border-zinc-200 bg-zinc-50/60 transition open:border-brand-200 open:bg-white open:shadow-sm">
                    <summary class="flex cursor-pointer list-none items-center gap-4 p-5 text-sm font-semibold text-zinc-900 [&::-webkit-details-marker]:hidden">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-600 transition group-open:bg-brand-600 group-open:text-white">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <span class="flex-1">{{ $q }}</span>
                        <span class="shrink-0 text-zinc-300 transition duration-200 group-open:rotate-90 group-open:text-brand-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m12.75 8.25-4.5 4.5 4.5 4.5"/></svg>
                        </span>
                    </summary>
                    <p class="border-t border-zinc-100 px-5 py-4 pl-16 text-sm leading-relaxed text-zinc-500">{{ $a }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    @endif


    {{-- ===== CTA ===== --}}
    <section class="py-20 md:py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-3xl bg-zinc-950 px-8 py-16 text-center md:py-20">
                <div class="pointer-events-none absolute -top-32 left-1/2 h-96 w-[640px] -translate-x-1/2 rounded-full bg-brand-600/30 blur-[100px]"></div>
                <div class="pointer-events-none absolute inset-0 opacity-35" style="background-image:linear-gradient(to right, rgba(255,255,255,.04) 1px, transparent 1px),linear-gradient(to bottom, rgba(255,255,255,.04) 1px, transparent 1px);background-size:56px 56px"></div>
                <h2 class="relative text-3xl font-bold tracking-tight text-white md:text-4xl">{{ __('landing.cta_title') }}</h2>
                <p class="relative mx-auto mt-4 max-w-xl text-lg text-zinc-400">{{ __('landing.cta_subtitle') }}</p>
                <div class="relative mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-10 py-4 text-base font-semibold text-zinc-900 shadow-xl transition hover:-translate-y-0.5 hover:bg-brand-50 sm:w-auto">
                        {{ __('landing.cta_primary') }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </a>
                    <a href="#pricing" class="inline-flex w-full items-center justify-center rounded-2xl border border-white/20 bg-white/5 px-8 py-4 text-base font-medium text-zinc-200 backdrop-blur transition hover:border-white/40 hover:text-white sm:w-auto">{{ __('landing.cta_secondary') }}</a>
                </div>
                <div class="relative mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-zinc-500">
                    @foreach (['landing.cta_badge_free', 'landing.cta_badge_nocard', 'landing.cta_badge_fast'] as $bk)
                    <span class="inline-flex items-center gap-1.5"><svg class="h-3.5 w-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>{{ __($bk) }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===== 页脚 ===== --}}
    <footer class="bg-zinc-950 text-zinc-400">
        <div class="mx-auto max-w-7xl px-6 pt-16 pb-8">
        <div class="mb-14 h-px rounded-full bg-gradient-to-r from-transparent via-zinc-700 to-transparent"></div>
            <div class="grid gap-10 md:grid-cols-5">
                <div class="md:col-span-2">
                    <x-brand-logo dark />
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">{{ __('landing.subtitle') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_product') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="#features" class="transition hover:text-white">{{ __('landing.nav_features') }}</a></li>
                        <li><a href="#pricing" class="transition hover:text-white">{{ __('landing.nav_pricing') }}</a></li>
                        <li><a href="{{ route('api.docs') }}" class="transition hover:text-white">{{ __('landing.footer_api') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_resources') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="{{ route('blog') }}" class="transition hover:text-white">{{ __('landing.nav_blog') }}</a></li>
                        <li><a href="{{ route('help') }}" class="transition hover:text-white">{{ __('landing.nav_help') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="transition hover:text-white">{{ __('landing.footer_contact') }}</a></li>
                        @if (\App\Support\Settings::get('seo.tools_is_enabled', true)
                            && (auth()->check() || in_array(\App\Support\Settings::get('seo.tools_guest_access'), [true, 'true', '1'], true)))
                        <li><a href="{{ route('seo.tools') }}" class="transition hover:text-white">{{ __('landing.nav_seo_tools') }}</a></li>
                        @endif
                        @if (\App\Support\Settings::get('seo.audits_is_enabled', true))
                        <li><a href="{{ route('seo.directory') }}" class="transition hover:text-white">{{ __('landing.nav_seo_directory') }}</a></li>
                        @endif
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_legal') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="{{ route('page', 'terms') }}" class="transition hover:text-white">{{ __('landing.footer_terms') }}</a></li>
                        <li><a href="{{ route('page', 'privacy') }}" class="transition hover:text-white">{{ __('landing.footer_privacy') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-14 border-t border-zinc-800/80 pt-8 text-center">
                <p class="text-sm text-zinc-500">© {{ date('Y') }} {{ \App\Support\Brand::name() }}. {{ __('landing.footer_rights') }}</p>
                {{-- ICP 备案号（后台 设置 → 品牌 → 页脚备案号） --}}
                @if ($icp = \App\Support\Brand::icp())
                <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer nofollow" class="mt-2 inline-block text-sm text-zinc-600 transition hover:text-zinc-400">{{ $icp }}</a>
                @endif
            </div>
        </div>
    </footer>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>


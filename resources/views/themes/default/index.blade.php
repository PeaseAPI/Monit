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

    {{-- ===== 顶部导航 ===== --}}
    <header class="sticky top-0 z-40 border-b border-zinc-100 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
            <x-brand-logo href="{{ route('index') }}" />

            <nav class="hidden items-center gap-8 md:flex">
                <a href="#features" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_features') }}</a>
                <a href="#showcase" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_showcase') }}</a>
                <a href="#pricing" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_pricing') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_blog') }}</a>
                <a href="{{ route('help') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_help') }}</a>
            </nav>

            <div class="flex items-center gap-3">
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
                <a href="{{ route('register') }}" class="w-full rounded-2xl bg-brand-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:bg-brand-700 sm:w-auto">
                    {{ __('landing.cta_primary') }} →
                </a>
                <a href="#showcase" class="w-full rounded-2xl border border-zinc-200 bg-white px-8 py-4 text-base font-semibold text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 sm:w-auto">
                    {{ __('landing.cta_secondary') }}
                </a>
            </div>
            <p class="mt-4 text-sm text-zinc-400">{{ __('landing.no_card_required') }}</p>

            {{-- 产品界面模拟（纯 CSS，无外部资源） --}}
            <div class="relative mx-auto mt-16 max-w-5xl">
                <div class="rounded-2xl border border-zinc-200 bg-white shadow-2xl shadow-zinc-900/10">
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
    </section>

    {{-- ===== 功能矩阵 ===== --}}
    <section id="features" class="border-t border-zinc-100 bg-zinc-50/50 py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold tracking-widest text-brand-600 uppercase">{{ __('landing.features_eyebrow') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.features_title') }}</h2>
                <p class="mt-4 text-lg text-zinc-500">{{ __('landing.features_subtitle') }}</p>
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
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

            <div class="mt-16 grid gap-6 md:grid-cols-3">
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
            <div class="mt-20 grid gap-6 md:grid-cols-3">
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


    {{-- ===== 定价 ===== --}}
    @if (\App\Support\Brand::showLandingPlans())
    <section id="pricing" class="py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.pricing_title') }}</h2>
                <p class="mt-4 text-lg text-zinc-500">{{ __('landing.pricing_subtitle') }}</p>
                @if (count($currencies ?? []) > 1)
                <form method="GET" action="{{ route('index') }}" class="mt-6 inline-flex items-center gap-2" id="#pricing">
                    <label for="landing-currency" class="text-sm text-zinc-500">{{ __('landing.currency') }}</label>
                    <select id="landing-currency" name="currency" onchange="this.form.submit()"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-700">
                        @foreach ($currencies as $code => $meta)
                            <option value="{{ $code }}" @selected($code === ($currency ?? 'CNY'))>{{ $code }} {{ $meta['symbol'] ?? '' }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <div class="mt-16 grid gap-6 md:grid-cols-3">
                @forelse ($plans ?? [] as $plan)
                @php($symbol = $currencies[$plan->landing_currency ?? ($currency ?? 'CNY')]['symbol'] ?? '¥')
                @php($featured = ($loop->count >= 3) && ($loop->middle ?? false))
                <div class="{{ $featured ? 'relative rounded-2xl border-2 border-brand-600 bg-white p-8 shadow-xl shadow-brand-600/10 md:-translate-y-3' : 'rounded-2xl border border-zinc-200 bg-white p-8' }}">
                    @if ($featured)
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-xs font-semibold text-white">{{ __('landing.popular') }}</span>
                    @endif
                    <h3 class="text-lg font-semibold text-zinc-900">{{ $plan->name }}</h3>
                    <p class="mt-3">
                        @if ($plan->landing_price !== null && (float) $plan->landing_price > 0)
                            <span class="text-4xl font-bold text-zinc-900">{{ $symbol }}{{ number_format((float) $plan->landing_price, 2) }}</span>
                            <span class="text-sm font-normal text-zinc-400">/{{ __('landing.per_month') }}</span>
                        @else
                            <span class="text-4xl font-bold text-zinc-900">{{ __('landing.free') }}</span>
                        @endif
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-500">{{ $plan->description }}</p>
                    <a href="{{ route('register') }}" class="{{ $featured ? 'mt-6 block rounded-xl bg-brand-600 px-6 py-3 text-center text-sm font-semibold text-white transition hover:bg-brand-700' : 'mt-6 block rounded-xl border border-zinc-300 px-6 py-3 text-center text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50' }}">
                        {{ __('landing.get_started') }}
                    </a>
                </div>
                @empty
                <p class="col-span-full text-center text-zinc-500">{{ __('common.no_plans') }}</p>
                @endforelse
            </div>
        </div>
    </section>
    @endif

    {{-- ===== FAQ ===== --}}
    <section class="border-t border-zinc-100 bg-zinc-50/50 py-24">
        <div class="mx-auto max-w-3xl px-6">
            <h2 class="text-center text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">{{ __('landing.faq_title') }}</h2>
            <div class="mt-12 space-y-4">
                @foreach ([
                    __('landing.faq_q1') => __('landing.faq_a1'),
                    __('landing.faq_q2') => __('landing.faq_a2'),
                    __('landing.faq_q3') => __('landing.faq_a3'),
                    __('landing.faq_q4') => __('landing.faq_a4'),
                    __('landing.faq_q5') => __('landing.faq_a5'),
                ] as $q => $a)
                <details class="group rounded-2xl border border-zinc-200 bg-white p-6 open:border-brand-200">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-semibold text-zinc-900 [&::-webkit-details-marker]:hidden">
                        {{ $q }}
                        <span class="ml-4 shrink-0 text-zinc-400 transition group-open:rotate-45">＋</span>
                    </summary>
                    <p class="mt-4 text-sm leading-relaxed text-zinc-500">{{ $a }}</p>
                </details>
                @endforeach
            </div>
        </div>
    </section>


    {{-- ===== CTA ===== --}}
    <section class="py-24">
        <div class="mx-auto max-w-7xl px-6">
            <div class="relative overflow-hidden rounded-3xl bg-zinc-950 px-8 py-16 text-center md:py-20">
                <div class="pointer-events-none absolute -top-32 left-1/2 h-96 w-[640px] -translate-x-1/2 rounded-full bg-brand-600/30 blur-[100px]"></div>
                <h2 class="relative text-3xl font-bold tracking-tight text-white md:text-4xl">{{ __('landing.cta_title') }}</h2>
                <p class="relative mx-auto mt-4 max-w-xl text-lg text-zinc-400">{{ __('landing.cta_subtitle') }}</p>
                <a href="{{ route('register') }}" class="relative mt-10 inline-block rounded-2xl bg-white px-10 py-4 text-base font-semibold text-zinc-900 transition hover:-translate-y-0.5 hover:bg-brand-50">
                    {{ __('landing.cta_primary') }} →
                </a>
            </div>
        </div>
    </section>

    {{-- ===== 页脚 ===== --}}
    <footer class="border-t border-zinc-100 bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16">
            <div class="grid gap-10 md:grid-cols-5">
                <div class="md:col-span-2">
                    <x-brand-logo />
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">{{ __('landing.subtitle') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900">{{ __('landing.footer_product') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="#features" class="transition hover:text-zinc-900">{{ __('landing.nav_features') }}</a></li>
                        <li><a href="#pricing" class="transition hover:text-zinc-900">{{ __('landing.nav_pricing') }}</a></li>
                        <li><a href="{{ route('api.docs') }}" class="transition hover:text-zinc-900">{{ __('landing.footer_api') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900">{{ __('landing.footer_resources') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="{{ route('blog') }}" class="transition hover:text-zinc-900">{{ __('landing.nav_blog') }}</a></li>
                        <li><a href="{{ route('help') }}" class="transition hover:text-zinc-900">{{ __('landing.nav_help') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="transition hover:text-zinc-900">{{ __('landing.footer_contact') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-900">{{ __('landing.footer_legal') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        <li><a href="{{ route('page', 'terms') }}" class="transition hover:text-zinc-900">{{ __('landing.footer_terms') }}</a></li>
                        <li><a href="{{ route('page', 'privacy') }}" class="transition hover:text-zinc-900">{{ __('landing.footer_privacy') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-zinc-100 pt-8">
                <div class="flex flex-col items-center justify-between gap-4 md:flex-row">
                    <p class="text-sm text-zinc-400">© {{ date('Y') }} {{ \App\Support\Brand::name() }}. {{ __('landing.footer_rights') }}</p>
                    {{-- ICP 备案号（后台 设置 → 品牌 → 页脚备案号） --}}
                    @if ($icp = \App\Support\Brand::icp())
                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer nofollow" class="text-sm text-zinc-400 transition hover:text-zinc-600">{{ $icp }}</a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>


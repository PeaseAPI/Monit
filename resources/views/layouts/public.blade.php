{{-- 公开内容页布局（SEO 工具中心 / 审计目录等宽内容页）
    与 layouts.guest（auth 表单品牌分栏）区分：本布局提供顶部导航，
    访客可自由往返首页/工具/目录，登录用户显示仪表盘入口 --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('seo.tools_title')) {{ \App\Support\Brand::titleSeparator() }} {{ \App\Support\Brand::name() }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    @include('parts.announcement_bar')

    {{-- 顶部导航条（与主题首页对齐：h-16 / px-6 / max-w-7xl） --}}
        <header class="sticky top-0 z-40 border-b border-zinc-100 bg-white/80 backdrop-blur-lg">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-6">
            <div class="flex min-w-0 items-center gap-6">
                                <x-brand-logo class="h-8 w-8 shrink-0" text-class="text-base" href="{{ route('index') }}"/>
                <nav class="hidden items-center gap-8 md:flex">
                    <a href="{{ route('index') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_home') }}</a>
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
            </div>
                        <div class="flex shrink-0 items-center gap-3">
                {{-- 语言切换器（与首页风格一致，原生 details 实现，无 JS 依赖） --}}
                @if (count((array) config('monit.locales')) > 1)
                <details class="group relative">
                    <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-zinc-200 px-3 py-1.5 text-sm text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 [&::-webkit-details-marker]:hidden">
                        <span>{{ config('monit.locales.'.app()->getLocale().'.flag', '🌐') }}</span>
                        <span class="hidden sm:inline">{{ config('monit.locales.'.app()->getLocale().'.label', app()->getLocale()) }}</span>
                        <svg class="h-3 w-3 text-zinc-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
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

                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">{{ __('dashboard.title') }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:block">{{ __('landing.nav_login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">{{ __('landing.nav_get_started') }}</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- 内容栏默认限宽居中；视图可用 @section('main_class') 覆盖（如 tools 页 hero 全宽） --}}
    <main class="@yield('main_class', 'mx-auto w-full max-w-7xl px-6 py-10')">
        @yield('content')
    </main>

    {{-- 页脚（与主题首页 footer 完全一致：深色多栏 + 版权 + ICP） --}}
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
                        <li><a href="{{ route('index') }}#features" class="transition hover:text-white">{{ __('landing.nav_features') }}</a></li>
                        <li><a href="{{ route('index') }}#pricing" class="transition hover:text-white">{{ __('landing.nav_pricing') }}</a></li>
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
                        @if (filter_var(\App\Support\Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN))
                        <li><a href="{{ route('seo.directory') }}" class="transition hover:text-white">{{ __('landing.nav_seo_directory') }}</a></li>
                        @endif
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-zinc-200">{{ __('landing.footer_legal') }}</h4>
                    <ul class="mt-4 space-y-2.5 text-sm text-zinc-500">
                        {{-- 法务链接（main.terms_and_conditions_url / privacy_policy_url）：外链优先，站内静态页兜底 --}}
                        @php($termsUrl = trim((string) \App\Support\Settings::get('main.terms_and_conditions_url', '')))
                        @php($privacyUrl = trim((string) \App\Support\Settings::get('main.privacy_policy_url', '')))
                        <li><a href="{{ $termsUrl !== '' ? $termsUrl : route('terms') }}"@if ($termsUrl !== '') target="_blank" rel="noopener"@endif class="transition hover:text-white">{{ __('landing.footer_terms') }}</a></li>
                        <li><a href="{{ $privacyUrl !== '' ? $privacyUrl : route('privacy') }}"@if ($privacyUrl !== '') target="_blank" rel="noopener"@endif class="transition hover:text-white">{{ __('landing.footer_privacy') }}</a></li>
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

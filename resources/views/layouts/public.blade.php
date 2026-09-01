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

    {{-- 顶部导航条 --}}
    <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-6">
                <x-brand-logo class="shrink-0" href="{{ route('index') }}"/>
                <nav class="hidden items-center gap-5 sm:flex">
                    <a href="{{ route('index') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_home') }}</a>
                    @if (\App\Support\Settings::get('seo.tools_is_enabled', true)
                        && (auth()->check() || in_array(\App\Support\Settings::get('seo.tools_guest_access'), [true, 'true', '1'], true)))
                        <a href="{{ route('seo.tools') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_seo_tools') }}</a>
                    @endif
                    @if (\App\Support\Settings::get('seo.audits_is_enabled', true))
                        <a href="{{ route('seo.directory') }}" class="text-sm font-medium text-zinc-600 transition hover:text-zinc-900">{{ __('landing.nav_seo_directory') }}</a>
                    @endif
                </nav>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">{{ __('dashboard.title') }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden text-sm font-medium text-zinc-600 transition hover:text-zinc-900 sm:block">{{ __('landing.nav_login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">{{ __('landing.nav_get_started') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6">
        @yield('content')
    </main>

    <footer class="border-t border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-zinc-500 sm:flex-row sm:px-6">
            <p>© {{ date('Y') }} {{ \App\Support\Brand::name() }} · {{ __('guest.self_hosted_oss') }}</p>
            <div class="flex items-center gap-4">
                @if (\App\Support\Settings::get('seo.audits_is_enabled', true))
                    <a href="{{ route('seo.directory') }}" class="transition hover:text-zinc-800">{{ __('landing.nav_seo_directory') }}</a>
                @endif
                <a href="{{ route('index') }}" class="transition hover:text-zinc-800">{{ __('landing.nav_home') }}</a>
            </div>
        </div>
    </footer>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>

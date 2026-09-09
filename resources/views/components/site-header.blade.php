{{-- 全站统一顶部导航组件（x-site-header）
    首页主题 / SEO 内容页 / 博客 / 帮助中心 / 定价页共用，
    保证任意公开页面头部完全一致：同 Logo、同菜单、同语言切换、同登录态。
    移动端用原生 <details> 汉堡菜单（无 JS 依赖）。 --}}
@php
    $seoToolsVisible = \App\Support\Settings::get('seo.tools_is_enabled', true)
        && (auth()->check() || in_array(\App\Support\Settings::get('seo.tools_guest_access'), [true, 'true', '1'], true));
    $seoDirectoryVisible = filter_var(\App\Support\Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN);
    $blogVisible = \App\Support\Settings::get('content.blog_is_enabled') === null
        || filter_var(\App\Support\Settings::get('content.blog_is_enabled'), FILTER_VALIDATE_BOOLEAN);
    // [文案键 => ['route' => 路由名, 'active' => 当前页]]，不可用的项过滤掉
    $navLinks = array_filter([
        'landing.nav_home' => ['route' => 'index', 'active' => request()->routeIs('index')],
        'landing.nav_pricing' => ['route' => 'plan', 'active' => request()->routeIs('plan')],
        'landing.nav_seo_tools' => $seoToolsVisible ? ['route' => 'seo.tools', 'active' => request()->routeIs('seo.tools')] : null,
        'landing.nav_seo_directory' => $seoDirectoryVisible ? ['route' => 'seo.directory', 'active' => request()->routeIs('seo.directory')] : null,
        'landing.nav_blog' => $blogVisible ? ['route' => 'blog', 'active' => request()->routeIs('blog*')] : null,
        'landing.nav_help' => ['route' => 'help', 'active' => request()->routeIs('help*')],
    ]);
@endphp
<header class="sticky top-0 z-40 border-b border-zinc-100 bg-white/80 backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-6">
        <div class="flex min-w-0 items-center gap-6">
            <x-brand-logo class="h-8 w-8 shrink-0" text-class="text-base" href="{{ route('index') }}"/>
            <nav class="hidden items-center gap-8 md:flex">
                @foreach ($navLinks as $labelKey => $link)
                    <a href="{{ route($link['route']) }}"
                       class="text-sm font-medium transition {{ ($link['active'] ?? false) ? 'text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}"
                       @if ($link['active'] ?? false) aria-current="page" @endif>{{ __($labelKey) }}</a>
                @endforeach
            </nav>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            {{-- 语言切换器（原生 details，无 JS 依赖） --}}
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


            {{-- 移动端汉堡菜单（原生 details） --}}
            <details class="group relative md:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-center rounded-xl border border-zinc-200 p-2 text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 [&::-webkit-details-marker]:hidden" aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <div class="absolute right-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-zinc-100 bg-white py-1.5 shadow-lg shadow-zinc-900/5">
                    @foreach ($navLinks as $labelKey => $link)
                        <a href="{{ route($link['route']) }}"
                           class="block px-4 py-2.5 text-sm {{ ($link['active'] ?? false) ? 'bg-brand-50 font-medium text-brand-700' : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}">{{ __($labelKey) }}</a>
                    @endforeach
                    @guest
                    <div class="my-1.5 h-px bg-zinc-100"></div>
                    <a href="{{ route('login') }}" class="block px-4 py-2.5 text-sm text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900">{{ __('landing.nav_login') }}</a>
                    @endguest
                </div>
            </details>
        </div>
    </div>
</header>

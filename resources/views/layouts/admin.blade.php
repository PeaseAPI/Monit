{{--
    管理后台布局 2.0（对标原版 monit.cn/admin 菜单结构 · 深色侧栏 SaaS 视觉升级）
    结构：admin-sidebar（品牌头/分区菜单/用户菜单）+ admin-content（顶栏/主区）
    菜单顺序与分组与原版一致：仪表台→用户→设置→套餐→语言→广播→通知→推送→插件→统计
    →资源(折叠)→博客(折叠)→API | 代码→税费→支付→推广提现 | 网站→热图→回放→批注→域名 | 用户日志
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.admin_panel')) · {{ \App\Support\Brand::name() }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100/60 font-sans text-zinc-900 antialiased">
<div class="flex min-h-screen">
    <div id="admin-sidebar-overlay" class="fixed inset-0 z-30 hidden bg-zinc-950/60 backdrop-blur-sm md:hidden"></div>

    {{-- 深色侧栏 --}}
    <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-white/5 bg-zinc-950 transition-transform duration-200 md:translate-x-0">
        {{-- 品牌头（渐变徽标 + 管理后台徽章）--}}
        <div class="flex h-16 shrink-0 items-center gap-2 border-b border-white/5 bg-gradient-to-r from-brand-600/10 via-transparent to-transparent px-5">
            <a href="{{ route('admin.index') }}" class="flex min-w-0 items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-700 shadow-lg shadow-brand-600/30">
                    <x-brand-logo class="h-9 w-9" text-class="hidden" />
                </span>
                <span class="truncate text-base font-bold tracking-tight text-white">{{ \App\Support\Brand::name() }}</span>
            </a>
            <span class="ml-auto shrink-0 rounded-md bg-brand-500/15 px-2 py-1 text-[11px] font-semibold text-brand-300 ring-1 ring-inset ring-brand-400/20">{{ __('admin.admin_label') }}</span>
            <button type="button" class="ml-0.5 shrink-0 rounded-lg p-1.5 text-zinc-500 hover:bg-white/5 md:hidden" onclick="window.adminToggleSidebar()">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- 分区菜单（顺序/折叠组与原版一致）--}}
        @php
            $icons = require resource_path('views/admin/partials/sidebar-icons.php');
            // 各控制器 adminNav 值规范化（旧值 → 原版菜单 key）
            $adminNavMap = [
                'blog_posts' => 'blog-posts',
                'affiliates' => 'affiliates-withdrawals',
                'logs' => 'users-logs',
                'push-subscribers' => 'push-notifications',
            ];
            $adminNav = $adminNavMap[$adminNav ?? ''] ?? ($adminNav ?? '');
        @endphp
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4 pt-2">
            @php
                $navItem = function (string $key, string $icon, string $label, string $url) use ($icons, $adminNav) {
                    $active = ($adminNav ?? '') === $key;
                    return '<a href="'.$url.'" class="admin-nav-link'.($active ? ' admin-nav-link-active' : '').'">'
                        .'<svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="'.$icons[$icon].'"/></svg>'
                        .'<span class="truncate">'.$label.'</span></a>';
                };
            @endphp
            <p class="admin-nav-heading">{{ __('admin.nav_section_overview') }}</p>
            {!! $navItem('dashboard', 'dashboard', __('admin.sidebar_dashboard'), route('admin.index')) !!}
            {!! $navItem('statistics', 'statistics', __('admin.sidebar_statistics'), route('admin.statistics')) !!}

            <p class="admin-nav-heading">{{ __('admin.nav_section_manage') }}</p>
            {!! $navItem('users', 'users', __('admin.sidebar_users'), route('admin.users.index')) !!}
            {!! $navItem('settings', 'settings', __('admin.sidebar_settings'), route('admin.settings.index')) !!}
            {!! $navItem('plans', 'plans', __('admin.sidebar_plans'), route('admin.plans.index')) !!}
            {!! $navItem('languages', 'languages', __('admin.sidebar_languages'), route('admin.languages.index')) !!}
            {!! $navItem('broadcasts', 'broadcasts', __('admin.sidebar_broadcasts'), route('admin.broadcasts.index')) !!}
            {!! $navItem('notifications', 'notifications', __('admin.sidebar_notifications'), route('admin.notifications.index')) !!}
            {!! $navItem('push-notifications', 'push', __('admin.sidebar_push_notifications'), route('admin.push-notifications.index')) !!}
            {!! $navItem('plugins', 'plugins', __('admin.sidebar_plugins'), route('admin.plugins.index')) !!}

            {{-- 资源折叠组（对标 admin_sidebar_resources_container）--}}
            @php($resourcesOpen = in_array($adminNav ?? '', ['pages-categories', 'pages']))
            <div>
                <button type="button" onclick="window.adminToggleGroup('admin-group-resources')" class="admin-nav-group w-full">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icons['info'] }}"/></svg>
                    <span class="truncate">{{ __('admin.sidebar_resources') }}</span>
                    <svg class="ml-auto h-3.5 w-3.5 shrink-0 transition-transform {{ $resourcesOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="admin-group-resources" class="{{ $resourcesOpen ? '' : 'hidden' }} mt-0.5 space-y-0.5 pl-5">
                    {!! $navItem('pages-categories', 'pages', __('admin.sidebar_categories'), route('admin.pages-categories.index')) !!}
                    {!! $navItem('pages', 'pages', __('admin.sidebar_pages'), route('admin.pages.index')) !!}
                </div>
            </div>

            {{-- 博客折叠组（对标 admin_sidebar_blog_container）--}}
            @php($blogOpen = in_array($adminNav ?? '', ['blog-posts-categories', 'blog-posts']))
            <div>
                <button type="button" onclick="window.adminToggleGroup('admin-group-blog')" class="admin-nav-group w-full">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $icons['blog'] }}"/></svg>
                    <span class="truncate">{{ __('admin.sidebar_blog') }}</span>
                    <svg class="ml-auto h-3.5 w-3.5 shrink-0 transition-transform {{ $blogOpen ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div id="admin-group-blog" class="{{ $blogOpen ? '' : 'hidden' }} mt-0.5 space-y-0.5 pl-5">
                    {!! $navItem('blog-posts-categories', 'blog', __('admin.sidebar_categories'), route('admin.blog-posts-categories.index')) !!}
                    {!! $navItem('blog-posts', 'blog', __('admin.sidebar_blog_posts'), route('admin.blog-posts.index')) !!}
                </div>
            </div>

            <p class="admin-nav-heading">{{ __('admin.nav_section_monetization') }}</p>
            {!! $navItem('api', 'api', __('admin.sidebar_api_docs'), route('api.docs')) !!}
            {!! $navItem('codes', 'codes', __('admin.sidebar_codes'), route('admin.codes.index')) !!}
            {!! $navItem('taxes', 'taxes', __('admin.sidebar_taxes'), route('admin.taxes.index')) !!}
            {!! $navItem('payments', 'payments', __('admin.sidebar_payments'), route('admin.payments.index')) !!}
            {!! $navItem('affiliates-withdrawals', 'wallet', __('admin.sidebar_affiliates_withdrawals'), route('admin.affiliates-withdrawals.index')) !!}

            <p class="admin-nav-heading">{{ __('admin.nav_section_data') }}</p>
            {!! $navItem('websites', 'websites', __('admin.sidebar_websites'), route('admin.websites.index')) !!}
            {!! $navItem('heatmaps', 'heatmaps', __('admin.sidebar_heatmaps'), route('admin.heatmaps.index')) !!}
            {!! $navItem('replays', 'replays', __('admin.sidebar_replays'), route('admin.replays.index')) !!}
            {!! $navItem('annotations', 'annotations', __('admin.sidebar_annotations'), route('admin.annotations.index')) !!}
            {!! $navItem('domains', 'domains', __('admin.sidebar_domains'), route('admin.domains.index')) !!}
            {!! $navItem('teams', 'users', __('admin.sidebar_teams'), route('admin.teams.index')) !!}

            <p class="admin-nav-heading">{{ __('admin.nav_section_system') }}</p>
            {!! $navItem('users-logs', 'logs', __('admin.sidebar_user_logs'), route('admin.users.logs')) !!}
        </nav>

        {{-- 侧栏底部用户菜单（对标 admin-sidebar-footer dropdown）--}}
        <div class="relative shrink-0 border-t border-white/5 p-3">
            <button type="button" onclick="window.adminToggleGroup('admin-user-menu')" class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-white/5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="admin-user-menu" class="absolute bottom-full left-3 right-3 z-50 mb-2 hidden overflow-hidden rounded-xl border border-white/10 bg-zinc-900 py-1 shadow-xl shadow-black/40">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white">
                    <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l-3 3m3-3v12M4 7l8-4 8 4"/></svg>
                    {{ __('admin.user_panel') }}</a>
                <a href="{{ route('account.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white">
                    <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                    {{ __('admin.user_account') }}</a>
                <a href="{{ route('referrals.index') }}" class="flex items-center gap-2.5 px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white">
                    <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>
                    {{ __('admin.user_referrals') }}</a>
                <div class="my-1 border-t border-white/5"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-400 hover:bg-red-500/10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        {{ __('admin.logout') }}</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- 主内容区（对标 admin-content）--}}
    <div class="flex w-full min-w-0 flex-col md:pl-64">
        <header class="sticky top-0 z-20 border-b border-zinc-200/80 bg-white/80 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 md:px-8">
                <button type="button" class="rounded-lg p-2 text-zinc-600 hover:bg-zinc-100 md:hidden" onclick="window.adminToggleSidebar()" aria-label="{{ __('admin.toggle_menu') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-base font-semibold text-zinc-900 md:text-lg">@yield('title', __('admin.admin_panel'))</h1>
                <a href="{{ route('dashboard') }}" class="ml-auto hidden shrink-0 items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 sm:flex">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l-3 3m3-3v12M4 7l8-4 8 4"/></svg>
                    {{ __('admin.user_panel') }}</a>
            </div>
        </header>

        <main class="flex-1 p-4 md:p-8">
            @if (session('success'))
                <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-zinc-200/60 px-4 py-4 text-center text-xs text-zinc-400 md:px-8">
            © {{ now()->format('Y') }} {{ \App\Support\Brand::name() }} · {{ __('admin.admin_label') }}
        </footer>
    </div>
</div>

<script>
    window.adminToggleSidebar = function () {
        document.getElementById('admin-sidebar').classList.toggle('-translate-x-full');
        document.getElementById('admin-sidebar-overlay').classList.toggle('hidden');
    };
    window.adminToggleGroup = function (id) {
        const el = document.getElementById(id);
        if (el) { el.classList.toggle('hidden'); }
    };
    document.getElementById('admin-sidebar-overlay')?.addEventListener('click', () => window.adminToggleSidebar());
    document.addEventListener('click', (e) => {
        const menu = document.getElementById('admin-user-menu');
        if (menu && !menu.classList.contains('hidden') && !e.target.closest('#admin-user-menu') && !e.target.closest('button[onclick*="admin-user-menu"]')) {
            menu.classList.add('hidden');
        }
    });
</script>
</body>
</html>


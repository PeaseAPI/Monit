<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Dashboard')) {{ \App\Support\Brand::titleSeparator() }} {{ \App\Support\Brand::name() }}</title>
    @include('parts.brand_head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    @include('parts.announcement_bar')
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col bg-zinc-950 md:flex">
            {{-- 品牌 logo 留出呼吸空间（用户反馈 #4：不再贴着左上角） --}}
            <div class="px-4 pt-5 pb-3">
                <x-brand-logo dark href="{{ route('dashboard') }}" text-class="text-lg" />
            </div>

            <nav class="mt-2 flex-1 space-y-1 px-3">
                {{--
                    Nav items (active highlight)
                    $nav = 'dashboard'|'websites'
                --}}
                @php
                    $nav = $nav ?? 'dashboard';
                    $unreadNotifications = auth()->user()->internalNotifications()->where('is_read', false)->count();
                    $topWebsites = auth()->user()->websites()->orderBy('website_id')->get();
                    $currentWebsite = $topWebsites->firstWhere('website_id', (int) session('current_website_id')) ?? $topWebsites->first();
                    $wid = $currentWebsite?->website_id;

                    // Affiliate 插件门控（规格 §14.7：停用即关闭入口；布尔以 'true'/'false' 字符串存储，须 filter_var 归一化）
                    $affiliateEnabled = filter_var(\App\Support\Settings::get('affiliate.affiliate_is_enabled', true), FILTER_VALIDATE_BOOLEAN);

                    // 统计入口（对标 monit.cn 侧边：pageviews/visitors/heatmaps/replays；无网站时整组隐藏）
                    $statsItems = $wid ? [
                        ['key' => 'stats', 'route' => 'stats.index', 'params' => ['website' => $wid], 'label' => __('stats.nav.overview'), 'icon' => 'pulse'],
                        ['key' => 'stats_realtime', 'route' => 'stats.realtime', 'params' => ['website' => $wid], 'label' => __('stats.nav.realtime'), 'icon' => 'bolt'],
                        ['key' => 'stats_visitors', 'route' => 'stats.visitors', 'params' => ['website' => $wid], 'label' => __('stats.nav.visitors'), 'icon' => 'users'],
                        ['key' => 'stats_heatmaps', 'route' => 'stats.heatmaps', 'params' => ['website' => $wid], 'label' => __('stats.nav.heatmaps'), 'icon' => 'grid'],
                        ['key' => 'stats_replays', 'route' => 'stats.replays', 'params' => ['website' => $wid], 'label' => __('stats.nav.replays'), 'icon' => 'play'],
                    ] : [];

                    $items = array_merge(
                        [
                            ['key' => 'dashboard', 'route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'chart'],
                        ],
                        $statsItems,
                        array_filter([
                            ['key' => 'websites', 'route' => 'websites.index', 'label' => __('Websites'), 'icon' => 'globe'],
                            ['key' => 'domains', 'route' => 'domains.index', 'label' => __('nav.domains'), 'icon' => 'server'],
                            ['key' => 'teams', 'route' => 'teams.index', 'label' => __('nav.teams'), 'icon' => 'users'],
                            ['key' => 'seo_audits', 'route' => 'seo.audits', 'label' => __('seo.nav_audits'), 'icon' => 'magnifier'],
                            ['key' => 'seo_keywords', 'route' => 'seo.keywords', 'label' => __('seo.nav_keywords'), 'icon' => 'target'],
                            ['key' => 'seo_backlinks', 'route' => 'seo.backlinks', 'label' => __('seo.nav_backlinks'), 'icon' => 'link'],
                            ['key' => 'seo_tools', 'route' => 'seo.tools', 'label' => __('seo.nav_tools'), 'icon' => 'wrench'],
                            ['key' => 'payments', 'route' => 'payments.index', 'label' => __('nav.payments'), 'icon' => 'card'],
                            // 推荐返佣：联盟停用时隐藏入口（array_filter 移除 null）
                            $affiliateEnabled ? ['key' => 'referrals', 'route' => 'referrals.index', 'label' => __('nav.referrals'), 'icon' => 'gift'] : null,
                            ['key' => 'notifications', 'route' => 'notifications.index', 'label' => __('nav.notifications'), 'icon' => 'bell', 'badge' => $unreadNotifications],
                            ['key' => 'account', 'route' => 'account.index', 'label' => __('nav.account'), 'icon' => 'user'],
                        ])
                    );
                @endphp
                @foreach ($items as $item)
                    <a href="{{ isset($item['params']) ? route($item['route'], $item['params']) : route($item['route']) }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                              {{ $nav === $item['key'] ? 'bg-brand-600/20 text-brand-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                                                @if ($item['icon'] === 'chart')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 8.5 8l4 4 8-8M21 4v6m0-6h-6"/></svg>
                        @elseif ($item['icon'] === 'magnifier')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        @elseif ($item['icon'] === 'target')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0-4.5a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0-3a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                        @elseif ($item['icon'] === 'link')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                        @elseif ($item['icon'] === 'card')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        @elseif ($item['icon'] === 'gift')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                        @elseif ($item['icon'] === 'user')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        @elseif ($item['icon'] === 'bell')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @elseif ($item['icon'] === 'server')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z"/></svg>
                        @elseif ($item['icon'] === 'pulse')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.75l2.25-7.5 3.75 13.5 2.25-9h3.75l3-4.5"/></svg>
                        @elseif ($item['icon'] === 'bolt')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                        @elseif ($item['icon'] === 'grid')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6v-6Zm10.5 0h6v6h-6v-6Zm-10.5 10.5h6v6h-6v-6Zm10.5 0h6v6h-6v-6Z"/></svg>
                        @elseif ($item['icon'] === 'play')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 5.653 13.5 6.347-13.5 6.347V5.653Z"/></svg>
                        @elseif ($item['icon'] === 'users')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                        @endif
                        {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="ml-auto rounded-full bg-rose-500/90 px-2 py-0.5 text-[11px] font-semibold leading-none text-white">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach

                {{-- 管理员可见：管理后台入口 --}}
                @if ((int) auth()->user()->type === 1)
                    <div class="mt-4 border-t border-zinc-800 pt-3">
                        <a href="{{ route('admin.index') }}"
                           class="group flex items-center gap-3 rounded-xl bg-gradient-to-r from-amber-500/15 to-transparent px-3 py-2.5 text-sm font-semibold text-amber-300 transition hover:from-amber-500/25">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.96 11.96 0 0 1 3.598 6 12 12 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286Z"/></svg>
                            {{ __('nav.admin') }}
                            <svg class="ml-auto h-4 w-4 opacity-50 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </a>
                    </div>
                @endif
            </nav>

            <div class="border-t border-zinc-900 p-3">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    @if (auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-brand-600/40"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <span class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600/30 text-sm font-semibold text-brand-300">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                    @else
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600/30 text-sm font-semibold text-brand-300">
                            {{ mb_substr(auth()->user()->name, 0, 1) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-200">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm text-zinc-500 transition hover:bg-zinc-900 hover:text-zinc-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex min-h-screen w-full flex-col md:pl-60">
            {{-- Top bar (desktop)：网站选择器 / 通知 / 用户菜单（对标 monit.cn 顶部导航） --}}
            <header class="sticky top-0 z-30 hidden items-center gap-3 border-b border-zinc-200 bg-white/85 px-6 py-2 backdrop-blur md:flex">
                {{-- 网站选择器 --}}
                <details class="group relative" data-topbar-details>
                    <summary class="flex cursor-pointer select-none list-none items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 [&::-webkit-details-marker]:hidden">
                        <svg class="h-5 w-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                        <span class="max-w-44 truncate">{{ $currentWebsite?->name ?? __('topbar.select_website') }}</span>
                        <svg class="h-4 w-4 text-zinc-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                    </summary>
                    <div class="absolute left-0 top-full z-40 mt-2 w-80 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl">
                        <input type="search" autocomplete="off"
                               placeholder="{{ __('topbar.search_website') }}"
                               oninput="var q=this.value.toLowerCase();document.querySelectorAll('#topbar-website-list > li').forEach(function(li){li.style.display=li.dataset.name.indexOf(q)>-1?'':'none'})"
                               class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm outline-none focus:border-brand-400 focus:bg-white">
                        <ul id="topbar-website-list" class="mt-2 max-h-72 overflow-y-auto">
                            @foreach ($topWebsites as $w)
                                <li data-name="{{ strtolower($w->name.' '.($w->host ?? $w->domain)) }}">
                                    <a href="{{ route('website.switch', $w->website_id) }}"
                                       class="flex items-center gap-3 rounded-xl px-3 py-2 transition hover:bg-zinc-50">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700">{{ mb_strtoupper(mb_substr($w->name, 0, 1)) }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-zinc-800">{{ $w->name }}</span>
                                            <span class="block truncate text-xs text-zinc-400">{{ $w->host ?? $w->domain }}</span>
                                        </span>
                                        @if ($w->website_id === $wid)
                                            <svg class="h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                            @if ($topWebsites->isEmpty())
                                <li class="px-3 py-6 text-center text-sm text-zinc-400">{{ __('topbar.no_websites') }}</li>
                            @endif
                        </ul>
                        <div class="mt-2 grid grid-cols-2 gap-2 border-t border-zinc-100 pt-2">
                            <a href="{{ route('websites.create') }}" class="rounded-xl bg-brand-600 px-3 py-2 text-center text-sm font-medium text-white transition hover:bg-brand-700">+ {{ __('topbar.add_website') }}</a>
                            <a href="{{ route('websites.index') }}" class="rounded-xl border border-zinc-200 px-3 py-2 text-center text-sm font-medium text-zinc-600 transition hover:bg-zinc-50">{{ __('topbar.manage_websites') }}</a>
                        </div>
                    </div>
                </details>

                <div class="ml-auto flex items-center gap-1.5">
                    {{-- 通知铃铛 --}}
                    <a href="{{ route('notifications.index') }}" title="{{ __('nav.notifications') }}"
                       class="relative flex h-10 w-10 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @if ($unreadNotifications > 0)
                            <span class="absolute right-1.5 top-1.5 rounded-full bg-rose-500 px-1.5 text-[10px] font-semibold leading-4 text-white">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                        @endif
                    </a>

                    {{-- 用户菜单（对标 monit.cn：账户/偏好/套餐/支付/推荐/API/团队/注销） --}}
                    <details class="group relative" data-topbar-details>
                        <summary class="flex cursor-pointer select-none list-none items-center gap-2.5 rounded-xl px-2.5 py-1.5 transition hover:bg-zinc-100 [&::-webkit-details-marker]:hidden">
                            @if (auth()->user()->avatar)
                                <img src="{{ auth()->user()->avatar }}" alt="" class="h-8 w-8 rounded-full object-cover ring-2 ring-brand-100">
                            @else
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            @endif
                            <span class="hidden min-w-0 text-left lg:block">
                                <span class="block max-w-32 truncate text-sm font-medium text-zinc-800">{{ auth()->user()->name }}</span>
                                <span class="block max-w-32 truncate text-xs text-zinc-400">{{ auth()->user()->email }}</span>
                            </span>
                            <svg class="h-4 w-4 text-zinc-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="absolute right-0 top-full z-40 mt-2 w-64 rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl">
                            <div class="border-b border-zinc-100 px-3 pb-2.5 pt-1.5">
                                <p class="truncate text-sm font-semibold text-zinc-800">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-zinc-400">{{ auth()->user()->email }}</p>
                            </div>
                            <nav class="mt-1.5 space-y-0.5">
                                @if ((int) auth()->user()->type === 1)
                                    <a href="{{ route('admin.index') }}" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-amber-600 transition hover:bg-amber-50">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.96 11.96 0 0 1 3.598 6 12 12 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.286Z"/></svg>
                                        {{ __('nav.admin') }}
                                    </a>
                                @endif
                                <a href="{{ route('account.index') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_account') }}</a>
                                <a href="{{ route('account.preferences') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_preferences') }}</a>
                                <a href="{{ route('account.plan') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_plan') }}</a>
                                <a href="{{ route('account.payments') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_payments') }}</a>
                                @if ($affiliateEnabled)
                                    <a href="{{ route('referrals.index') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_referrals') }}</a>
                                @endif
                                <a href="{{ route('account-api.index') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_api') }}</a>
                                <a href="{{ route('teams.index') }}" class="block rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50">{{ __('topbar.menu_teams') }}</a>
                            </nav>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1.5 border-t border-zinc-100 pt-1.5">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm text-zinc-500 transition hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </header>

            {{-- Top bar (mobile) --}}
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-zinc-200 bg-white/80 px-4 py-3 backdrop-blur md:hidden">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <x-brand-logo class="h-8 w-8" text-class="text-base" />
                </a>
                <div class="flex items-center gap-3">
                    @if ((int) auth()->user()->type === 1)
                        <a href="{{ route('admin.index') }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700">{{ __('nav.admin') }}</a>
                    @endif
                    <a href="{{ route('websites.index') }}" class="text-sm text-zinc-600">{{ __('Websites') }}</a>
                    <a href="{{ route('teams.index') }}" class="text-sm text-zinc-600">{{ __('nav.teams') }}</a>
                    <a href="{{ route('notifications.index') }}" class="relative text-sm text-zinc-600">{{ __('nav.notifications') }}
                        @php $unread = auth()->user()->internalNotifications()->where('is_read', false)->count(); @endphp
                        @if ($unread > 0)<span class="absolute -right-2 -top-1.5 rounded-full bg-rose-500 px-1.5 text-[10px] font-semibold leading-4 text-white">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-zinc-500">{{ __('Log out') }}</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8">
                {{-- 内容限宽居中（宽屏可读性）：页面自带 max-w-* 的仍会正常嵌套 --}}
                <div class="mx-auto w-full max-w-7xl">
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- 顶部栏 details dropdown：点击外部自动收起 --}}
    <script>
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details[data-topbar-details][open]').forEach(function (d) {
                if (!d.contains(e.target)) { d.removeAttribute('open'); }
            });
        });
    </script>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
    @stack('scripts')
</body>
</html>

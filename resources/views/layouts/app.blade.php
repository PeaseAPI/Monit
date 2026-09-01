<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', __('Dashboard')) · {{ \App\Support\Brand::name() }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    @include('parts.announcement_bar')
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col bg-zinc-950 md:flex">
            <x-brand-logo dark href="{{ route('dashboard') }}" text-class="text-lg" />

            <nav class="mt-2 flex-1 space-y-1 px-3">
                {{--
                    Nav items (active highlight)
                    $nav = 'dashboard'|'websites'
                --}}
                @php
                    $nav = $nav ?? 'dashboard';
                                        $items = [
                                                ['key' => 'dashboard', 'route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'chart'],
                        ['key' => 'websites', 'route' => 'websites.index', 'label' => __('Websites'), 'icon' => 'globe'],
                        ['key' => 'seo_audits', 'route' => 'seo.audits', 'label' => __('seo.nav_audits'), 'icon' => 'magnifier'],
                        ['key' => 'seo_keywords', 'route' => 'seo.keywords', 'label' => __('seo.nav_keywords'), 'icon' => 'target'],
                        ['key' => 'seo_backlinks', 'route' => 'seo.backlinks', 'label' => __('seo.nav_backlinks'), 'icon' => 'link'],
                        ['key' => 'payments', 'route' => 'payments.index', 'label' => __('nav.payments'), 'icon' => 'card'],
                        ['key' => 'referrals', 'route' => 'referrals.index', 'label' => __('nav.referrals'), 'icon' => 'gift'],
                        ['key' => 'account', 'route' => 'account.index', 'label' => __('nav.account'), 'icon' => 'user'],
                    ];
                @endphp
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}"
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
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                        @endif
                        {{ $item['label'] }}
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
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600/30 text-sm font-semibold text-brand-300">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
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
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-zinc-500">{{ __('Log out') }}</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-8">
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
            </main>
        </div>
    </div>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>

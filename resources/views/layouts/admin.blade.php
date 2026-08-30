<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', __('admin.admin_panel')) · Monit</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col bg-zinc-950 md:flex">
            <a href="{{ route('admin.index') }}" class="flex items-center gap-2.5 border-b border-zinc-800 px-5 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-red-700 text-lg font-bold text-white">M</span>
                <span class="text-lg font-semibold text-white">Monit</span>
                <span class="ml-auto rounded bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-400">{{ __('admin.admin_label') }}</span>
            </a>

                        <nav class="mt-4 flex-1 space-y-1 px-3">
                @php($adminNav = $adminNav ?? '')
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'dashboard' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                        {{ __('admin.dashboard') }}
                </a>
                <a href="{{ route('admin.statistics') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'statistics' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    {{ __('admin.data_statistics') }}
                </a>
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('admin.system_management') }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'users' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        {{ __('admin.user_management') }}
                </a>
                <a href="{{ route('admin.websites.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'websites' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        {{ __('admin.website_management') }}
                </a>
                <a href="{{ route('admin.domains.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'domains' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ __('admin.domain_management') }}
                </a>
                <a href="{{ route('admin.teams.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'teams' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 3.13a4 4 0 010 7.75M12 7a4 4 0 11-8 0 4 4 0 018 0zm0 13v-1a4 4 0 00-4-4H7a4 4 0 00-4 4v1h9zm8-1v-1a4 4 0 00-3-3.87"/></svg>
                                        {{ __('admin.teams') }}
                </a>
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('admin.commerce') }}</p>
                </div>
                <a href="{{ route('admin.plans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'plans' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        {{ __('admin.plan_management') }}
                </a>
                <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'payments' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        {{ __('admin.payment_records') }}
                </a>
                <a href="{{ route('admin.affiliates-withdrawals.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'affiliates' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v10m0 0l-3.5-3.5M12 13l3.5-3.5M4 15v3a3 3 0 003 3h10a3 3 0 003-3v-3"/></svg>
                                        {{ __('admin.affiliate_withdrawals') }}
                </a>
                <a href="{{ route('admin.taxes.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'taxes' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                        {{ __('admin.tax_config') }}
                </a>
                <a href="{{ route('admin.codes.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'codes' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V7zM5 15a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2v-2zm0 0V9a4 4 0 014-4h4m-4 10h4a4 4 0 004-4V9"/></svg>
                                        {{ __('admin.redeem_codes') }}
                </a>
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('admin.content_management') }}</p>
                </div>
                <a href="{{ route('admin.blog-posts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'blog_posts' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 13a2 2 0 002-2V9m-4 11a2 2 0 01-2-2V9a2 2 0 012-2h2a2 2 0 012 2v8a2 2 0 01-2 2h-2z"/></svg>
                                        {{ __('admin.blog_posts') }}
                </a>
                <a href="{{ route('admin.pages.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'pages' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        {{ __('admin.pages') }}
                </a>
                <a href="{{ route('admin.broadcasts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'broadcasts' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683a4.001 4.001 0 01 0-7.366M13 7a4 4 0 01-7.564 1.683"/></svg>
                                        {{ __('admin.broadcasts') }}
                </a>
                <a href="{{ route('admin.notifications.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'notifications' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        {{ __('admin.internal_notifications') }}
                </a>
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('admin.platform_ops') }}</p>
                </div>
                <a href="{{ route('admin.annotations.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'annotations' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 20l4-16m2 16l-4-16M6 9h14M4 15h14"/></svg>
                                        {{ __('admin.annotations') }}
                </a>
                <a href="{{ route('admin.heatmaps.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'heatmaps' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-9a2 2 0 012-2h2a2 2 0 012 2m0 4V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2"/></svg>
                                        {{ __('admin.heatmaps') }}
                </a>
                <a href="{{ route('admin.replays.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'replays' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        {{ __('admin.replays') }}
                </a>
                <a href="{{ route('admin.logs.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'logs' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        {{ __('admin.account_logs') }}
                </a>
                <a href="{{ route('admin.languages.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'languages' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                                        {{ __('admin.languages') }}
                </a>
                <a href="{{ route('admin.push-subscribers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'push-subscribers' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        {{ __('admin.push_subscribers') }}
                </a>
                <div class="mt-4 pt-4 border-t border-zinc-800">
                    <p class="px-3 mb-2 text-xs font-semibold text-zinc-500 uppercase tracking-wider">{{ __('admin.settings') }}</p>
                </div>
                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'settings' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                                        {{ __('admin.system_settings') }}
                </a>
                <a href="{{ route('admin.license.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'license' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        {{ __('admin.license_title') }}
                </a>
                <a href="{{ route('admin.plugins.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $adminNav === 'plugins' ? 'bg-red-600/20 text-red-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                                        {{ __('admin.plugins_title') }}
                </a>
            </nav>

            <div class="border-t border-zinc-800 p-3">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-600/30 text-sm font-semibold text-red-300">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-200">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ __('admin.administrator') }}</p>
                    </div>
                </div>
                <div class="mt-2 flex gap-2">
                                        <a href="{{ route('dashboard') }}" class="flex-1 rounded-xl px-3 py-2 text-center text-sm text-zinc-400 transition hover:bg-zinc-900 hover:text-zinc-200">{{ __('admin.user_view') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full rounded-xl px-3 py-2 text-center text-sm text-zinc-500 transition hover:bg-zinc-900 hover:text-zinc-200">{{ __('admin.logout') }}</button>
                    </form>
                </div>
            </div>
        </aside>
        <div class="flex w-full flex-col md:pl-64">
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-zinc-200 bg-white/80 px-4 py-3 backdrop-blur md:hidden">
                <a href="{{ route('admin.index') }}" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-red-500 to-red-700 text-sm font-bold text-white">M</span>
                                        <span class="font-semibold">{{ __('admin.admin_panel') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-zinc-500">{{ __('admin.logout') }}</button>
                </form>
            </header>
            <main class="flex-1 p-4 md:p-8">
                @if (session('success'))<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
                @if ($errors->any())<div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

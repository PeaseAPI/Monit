<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '仪表盘') · Monit</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="flex min-h-screen">
        {{-- 侧边栏 --}}
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col bg-zinc-950 md:flex">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-5 py-5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-lg font-bold text-white">M</span>
                <span class="text-lg font-semibold text-white">Monit</span>
            </a>

            <nav class="mt-2 flex-1 space-y-1 px-3">
                {{--
                    导航项（active 高亮）
                    $nav = 'dashboard'|'websites'
                --}}
                @php
                    $nav = $nav ?? 'dashboard';
                    $items = [
                        ['key' => 'dashboard', 'route' => 'dashboard', 'label' => '仪表盘', 'icon' => 'chart'],
                        ['key' => 'websites', 'route' => 'websites.index', 'label' => '网站管理', 'icon' => 'globe'],
                    ];
                @endphp
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                              {{ $nav === $item['key'] ? 'bg-brand-600/20 text-brand-300' : 'text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100' }}">
                        @if ($item['icon'] === 'chart')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5 8.5 8l4 4 8-8M21 4v6m0-6h-6"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.5-2.5 4-5.5 4-9s-1.5-6.5-4-9m0 18c-2.5-2.5-4-5.5-4-9s1.5-6.5 4-9M3.5 9h17m-17 6h17"/></svg>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
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
                    <button type="submit" class="mt-1 w-full rounded-xl px-3 py-2 text-left text-sm text-zinc-500 transition hover:bg-zinc-900 hover:text-zinc-200">
                        退出登录
                    </button>
                </form>
            </div>
        </aside>

        {{-- 主内容 --}}
        <div class="flex min-h-screen w-full flex-col md:pl-60">
            {{-- 顶部栏（移动端） --}}
            <header class="sticky top-0 z-20 flex items-center justify-between border-b border-zinc-200 bg-white/80 px-4 py-3 backdrop-blur md:hidden">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 text-sm font-bold text-white">M</span>
                    <span class="font-semibold">Monit</span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="{{ route('websites.index') }}" class="text-sm text-zinc-600">网站</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-zinc-500">退出</button>
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
</body>
</html>

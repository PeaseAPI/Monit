<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Monit') · Monit 网站分析</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- 品牌侧区 --}}
        <aside class="relative hidden w-full overflow-hidden bg-zinc-950 lg:flex lg:w-[46%] lg:flex-col lg:justify-between lg:p-12">
            <div class="pointer-events-none absolute -top-40 -left-40 h-[480px] w-[480px] rounded-full bg-brand-600/30 blur-[120px]"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-20 h-[420px] w-[420px] rounded-full bg-brand-400/20 blur-[120px]"></div>

            <a href="{{ route('index') }}" class="relative flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-lg font-bold text-white">M</span>
                <span class="text-xl font-semibold text-white">Monit</span>
            </a>

            <div class="relative">
                <h1 class="text-4xl leading-tight font-bold text-white">
                    自托管网站分析，<br>
                    <span class="bg-gradient-to-r from-brand-300 to-brand-500 bg-clip-text text-transparent">数据由你掌控</span>
                </h1>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-zinc-400">
                    隐私优先 · 无 Cookie 跟踪 · 多域名支持。<br>
                    轻量像素 &lt; 6kB，不拖慢你的网站。
                </p>
            </div>

            <p class="relative text-sm text-zinc-500">© {{ date('Y') }} Monit · 自托管开源方案</p>
        </aside>

        {{-- 表单主区 --}}
        <main class="flex w-full flex-1 items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

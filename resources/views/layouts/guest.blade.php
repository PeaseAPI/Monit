<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', \App\Support\Brand::name()) {{ \App\Support\Brand::titleSeparator() }} {{ __('app.tagline') }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    @include('parts.announcement_bar')
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- Brand sidebar --}}
        <aside class="relative hidden w-full overflow-hidden bg-zinc-950 lg:flex lg:w-[46%] lg:flex-col lg:justify-between lg:p-12">
            <span class="grid-pattern absolute inset-0"></span>
            <div class="pointer-events-none absolute -top-40 -left-40 h-[480px] w-[480px] rounded-full bg-brand-600/30 blur-[120px]"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-20 h-[420px] w-[420px] rounded-full bg-brand-400/20 blur-[120px]"></div>

            <x-brand-logo dark href="{{ route('index') }}"/>

            <div class="relative">
                <h1 class="text-4xl leading-tight font-bold text-white">
                    {{ __('guest.self_hosted_analytics') }}<br>
                    <span class="bg-gradient-to-r from-brand-300 to-brand-500 bg-clip-text text-transparent">{{ __('guest.your_data_your_control') }}</span>
                </h1>

                {{-- 特性清单（复用 guest.* 语言键） --}}
                <ul class="mt-9 space-y-4">
                    @foreach ([
                        ['icon' => 'shield', 'text' => __('guest.privacy_features')],
                        ['icon' => 'bolt', 'text' => __('guest.lightweight_pixel_feature')],
                        ['icon' => 'database', 'text' => __('guest.data_control')],
                        ['icon' => 'server', 'text' => __('guest.self_hosted_solution')],
                    ] as $feature)
                    <li class="flex items-center gap-3.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-brand-300">
                            @if ($feature['icon'] === 'shield')
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v6c0 4.5-3.5 8-8 9-4.5-1-8-4.5-8-9V6l8-3z"/></svg>
                            @elseif ($feature['icon'] === 'bolt')
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                            @elseif ($feature['icon'] === 'database')
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                            @else
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-3.269-9.956A3.75 3.75 0 0014.782 3.75h-5.564a3.75 3.75 0 00-3.579 2.586L2.37 15.992a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z"/></svg>
                            @endif
                        </span>
                        <span class="text-sm leading-relaxed text-zinc-300">{{ $feature['text'] }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-sm text-zinc-500">© {{ date('Y') }} {{ \App\Support\Brand::name() }} · {{ __('guest.self_hosted_oss') }}</p>
        </aside>

        {{-- Form main area --}}
        <main class="relative flex w-full flex-1 items-center justify-center overflow-hidden p-6 lg:p-12">
            {{-- 浅色装饰光晕 --}}
            <div class="pointer-events-none absolute -top-32 -right-32 h-96 w-96 rounded-full bg-brand-100/60 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-32 h-96 w-96 rounded-full bg-brand-50 blur-3xl"></div>

            {{-- 宽度扩展点：auth 页默认 max-w-md；公开统计页覆写为 max-w-7xl --}}
            <div class="relative w-full @yield('container', 'max-w-md')">
                {{-- 页面级 flash（字段级错误由各表单 @error 内联显示） --}}
                @if (session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-700">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>

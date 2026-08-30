<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', \App\Support\Brand::name()) · {{ __('app.tagline') }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        {{-- Brand sidebar --}}
        <aside class="relative hidden w-full overflow-hidden bg-zinc-950 lg:flex lg:w-[46%] lg:flex-col lg:justify-between lg:p-12">
            <div class="pointer-events-none absolute -top-40 -left-40 h-[480px] w-[480px] rounded-full bg-brand-600/30 blur-[120px]"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-20 h-[420px] w-[420px] rounded-full bg-brand-400/20 blur-[120px]"></div>

            <x-brand-logo dark href="{{ route('index') }}" />

                        <div class="relative">
                <h1 class="text-4xl leading-tight font-bold text-white">
                    {{ __('guest.self_hosted_analytics') }}<br>
                    <span class="bg-gradient-to-r from-brand-300 to-brand-500 bg-clip-text text-transparent">{{ __('guest.your_data_your_control') }}</span>
                </h1>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-zinc-400">
                    {{ __('guest.privacy_features') }}<br>
                    {{ __('guest.lightweight_pixel_feature') }}
                </p>
            </div>

            <p class="relative text-sm text-zinc-500">© {{ date('Y') }} {{ \App\Support\Brand::name() }} · {{ __('guest.self_hosted_oss') }}</p>
        </aside>

        {{-- Form main area --}}
        <main class="flex w-full flex-1 items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                @yield('content')
            </div>
        </main>
    </div>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>

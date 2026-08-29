<nav class="sticky top-0 z-20 border-b border-zinc-200 bg-white/80 backdrop-blur">
<div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
    <a href="{{ route('index') }}" class="text-lg font-bold text-zinc-900">Monit</a>
    <div class="flex items-center gap-5 text-sm">
        <a href="{{ route('blog') }}" class="text-zinc-600 hover:text-zinc-900">{{ __('nav.blog') }}</a>
        <a href="{{ route('help') }}" class="text-zinc-600 hover:text-zinc-900">{{ __('nav.help') }}</a>
        <a href="{{ route('plan') }}" class="text-zinc-600 hover:text-zinc-900">{{ __('nav.pricing') }}</a>
        @guest
        <a href="{{ route('login') }}" class="rounded-xl border border-zinc-300 px-4 py-2 text-zinc-700 hover:bg-zinc-50">{{ __('nav.login') }}</a>
        <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-4 py-2 font-medium text-white hover:bg-brand-700">{{ __('nav.register') }}</a>
        @endguest
        @auth
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-zinc-600 hover:text-zinc-900">{{ __('nav.dashboard') }}</a>
            <a href="{{ route('websites.index') }}" class="text-zinc-600 hover:text-zinc-900">{{ __('nav.websites') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-zinc-600 hover:text-zinc-900">{{ __('nav.logout') }}</button></form>
        </div>
        @endauth
    </div>
</div>
</nav>
@extends('layouts.guest')

@section('title', __('auth.login_btn'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <h2 class="text-2xl font-bold">{{ __('auth.welcome_back') }}</h2>
    <p class="mt-2 text-sm text-zinc-500">{{ __('auth.login_subtitle') }}</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700">{{ __('auth.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   placeholder="you@example.com"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700">{{ __('auth.password') }}</label>
            <input id="password" type="password" name="password" required
                   placeholder="••••••••"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-zinc-600">
            <input type="checkbox" name="remember" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
            {{ __('auth.remember_me') }}
        </label>

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
            {{ __('auth.login_btn') }}
        </button>
    </form>

    <p class="mt-6 text-sm text-zinc-500">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('auth.register_free') }}</a>
    </p>

    @include('partials.social-login')
@endsection

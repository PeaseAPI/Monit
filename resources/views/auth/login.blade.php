@extends('layouts.guest')

@section('title', __('auth.login_btn'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <div class="rounded-3xl border border-zinc-200/70 bg-white p-8 shadow-xl shadow-zinc-900/[0.04]">
        <h2 class="text-2xl font-bold tracking-tight">{{ __('auth.welcome_back') }}</h2>
        <p class="mt-2 text-sm text-zinc-500">{{ __('auth.login_subtitle') }}</p>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="email" class="form-label">{{ $phoneLoginEnabled ? __('auth.login_identifier') : __('auth.email') }}</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       placeholder="{{ $phoneLoginEnabled ? __('auth.login_identifier_placeholder') : 'you@example.com' }}"
                       class="form-input @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="form-label">{{ __('auth.password') }}</label>
                <input id="password" type="password" name="password" {{ $phoneLoginEnabled ? '' : 'required' }} autocomplete="current-password"
                       placeholder="••••••••"
                       class="form-input @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($phoneLoginEnabled)
                <div>
                    <label for="sms_code" class="form-label">{{ __('auth.sms_code') }}</label>
                    <input id="sms_code" type="text" inputmode="numeric" maxlength="6" name="sms_code" value="{{ old('sms_code') }}" autocomplete="one-time-code"
                           placeholder="{{ __('auth.sms_code_placeholder') }}"
                           class="form-input @error('sms_code') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                    <p class="mt-1 text-xs text-zinc-400">{{ __('auth.sms_code_optional_hint') }}</p>
                    @error('sms_code')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-zinc-600">
                    <input type="checkbox" name="remember" class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                    {{ __('auth.remember_me') }}
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 transition hover:text-brand-500">{{ __('auth.forgot_password') }}</a>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                {{ __('auth.login_btn') }}
            </button>
        </form>
    </div>

    @if($phoneLoginEnabled)
        <form method="POST" action="{{ route('sms.send') }}" class="mt-4 rounded-2xl border border-zinc-200/70 bg-white p-4">
            @csrf
            <input type="hidden" name="purpose" value="login">
            <p class="text-xs font-medium text-zinc-500">{{ __('auth.get_sms_code') }}</p>
            <div class="mt-2 flex gap-2">
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                       placeholder="{{ __('auth.phone_placeholder') }}"
                       class="form-input !w-auto flex-1 @error('phone') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                <button type="submit"
                        class="whitespace-nowrap rounded-xl border border-brand-600 px-4 py-2 text-sm font-medium text-brand-600 transition hover:bg-brand-50">
                    {{ __('auth.send_sms_code') }}
                </button>
            </div>
            @error('phone')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>
    @endif

    <p class="mt-6 text-sm text-zinc-500">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('auth.register_free') }}</a>
    </p>

    @include('partials.social-login')
@endsection

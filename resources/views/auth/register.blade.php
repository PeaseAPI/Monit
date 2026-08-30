@extends('layouts.guest')

@section('title', __('auth.register'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <h2 class="text-2xl font-bold">{{ __('auth.create_account') }}</h2>
    <p class="mt-2 text-sm text-zinc-500">{{ __('auth.free_start') }}</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-zinc-700">{{ __('auth.username') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                   placeholder="{{ __('auth.your_name') }}"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700">{{ __('auth.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   placeholder="you@example.com"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('email') border-red-400 @enderror">
            @error('email')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700">{{ __('auth.password') }}</label>
            <input id="password" type="password" name="password" required
                   placeholder="{{ __('auth.password_min') }}"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('password') border-red-400 @enderror">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if($smsRegisterEnabled ?? false)
            <div>
                <label for="phone" class="block text-sm font-medium text-zinc-700">{{ __('auth.phone') }}</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                       placeholder="{{ __('auth.phone_placeholder') }}"
                       class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('phone') border-red-400 @enderror">
                @error('phone')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sms_code" class="block text-sm font-medium text-zinc-700">{{ __('auth.sms_code') }}</label>
                <input id="sms_code" type="text" inputmode="numeric" maxlength="6" name="sms_code" value="{{ old('sms_code') }}" required autocomplete="one-time-code"
                       placeholder="{{ __('auth.sms_code_placeholder') }}"
                       class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('sms_code') border-red-400 @enderror">
                @error('sms_code')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <button type="submit"
                class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
            {{ __('auth.register_btn') }}
        </button>
    </form>

    @if($smsRegisterEnabled ?? false)
        <form method="POST" action="{{ route('sms.send') }}" class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
            @csrf
            <input type="hidden" name="purpose" value="register">
            <p class="text-xs font-medium text-zinc-500">{{ __('auth.get_sms_code') }}</p>
            <div class="mt-2 flex gap-2">
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                       placeholder="{{ __('auth.phone_placeholder') }}"
                       class="block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('phone') border-red-400 @enderror">
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
        {{ __('auth.already_have_account') }}
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('auth.login_now') }}</a>
    </p>

    @include('partials.social-login')
@endsection

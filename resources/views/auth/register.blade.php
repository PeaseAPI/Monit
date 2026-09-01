@extends('layouts.guest')

@section('title', __('auth.register'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <div class="rounded-3xl border border-zinc-200/70 bg-white p-8 shadow-xl shadow-zinc-900/[0.04]">
        <h2 class="text-2xl font-bold tracking-tight">{{ __('auth.create_account') }}</h2>
        <p class="mt-2 text-sm text-zinc-500">{{ __('auth.free_start') }}</p>

        <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="form-label">{{ __('auth.username') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                       placeholder="{{ __('auth.your_name') }}"
                       class="form-input @error('name') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                @error('name')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="form-label">{{ __('auth.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                       placeholder="you@example.com"
                       class="form-input @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                @error('email')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="form-label">{{ __('auth.password') }}</label>
                <input id="password" type="password" name="password" required
                       placeholder="{{ __('auth.password_min') }}"
                       class="form-input @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                @error('password')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @if($smsRegisterEnabled ?? false)
                <div>
                    <label for="phone" class="form-label">{{ __('auth.phone') }}</label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                           placeholder="{{ __('auth.phone_placeholder') }}"
                           class="form-input @error('phone') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                    @error('phone')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sms_code" class="form-label">{{ __('auth.sms_code') }}</label>
                    <input id="sms_code" type="text" inputmode="numeric" maxlength="6" name="sms_code" value="{{ old('sms_code') }}" required autocomplete="one-time-code"
                           placeholder="{{ __('auth.sms_code_placeholder') }}"
                           class="form-input @error('sms_code') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                    @error('sms_code')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- 人机验证（captcha.captcha_on_register）：开启时渲染供应商 widget --}}
            @php($captcha = \App\Support\Captcha::widget('register'))
            @if ($captcha)
                <div>
                    {!! $captcha !!}
                    @error('captcha')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- 条款同意（users.user_registration_require_consent） --}}
            @if ($requireConsent ?? false)
                <div>
                    <label class="flex items-start gap-2.5 text-sm text-zinc-600">
                        <input type="checkbox" name="terms" value="1" required
                               class="mt-0.5 rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            {{ __('auth.terms_consent') }}
                            <a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">{{ __('auth.terms_link') }}</a>
                        </span>
                    </label>
                    @error('terms')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <button type="submit"
                    class="w-full rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                {{ __('auth.register_btn') }}
            </button>
        </form>
    </div>

    @if($smsRegisterEnabled ?? false)
        <form method="POST" action="{{ route('sms.send') }}" class="mt-4 rounded-2xl border border-zinc-200/70 bg-white p-4">
            @csrf
            <input type="hidden" name="purpose" value="register">
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
        {{ __('auth.already_have_account') }}
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('auth.login_now') }}</a>
    </p>

    @include('partials.social-login')
@endsection

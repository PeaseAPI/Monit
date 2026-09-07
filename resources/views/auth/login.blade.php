@extends('layouts.guest')

@section('title', __('auth.login_btn'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <div class="rounded-3xl border border-zinc-200/70 bg-white p-8 shadow-xl shadow-zinc-900/[0.04]">
        <h2 class="text-2xl font-bold tracking-tight">{{ __('auth.welcome_back') }}</h2>
        <p class="mt-2 text-sm text-zinc-500">{{ __('auth.login_subtitle') }}</p>

        {{-- 社交登录 / OAuth 错误 --}}
        @error('provider')
            <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('oauth')
            <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        {{-- Tab 切换 --}}
        @if($phoneLoginEnabled)
        <div class="mt-6 flex rounded-xl bg-zinc-100 p-1" role="tablist">
            <label class="flex-1 cursor-pointer text-center" role="tab">
                <input type="radio" name="login_tab" value="password" class="peer sr-only" checked
                       onchange="document.getElementById('tab-password').hidden=false;document.getElementById('tab-sms').hidden=true;">
                <span class="block rounded-lg px-3 py-2 text-sm font-medium text-zinc-500 transition peer-checked:bg-white peer-checked:text-zinc-900 peer-checked:shadow-sm">
                    {{ __('auth.tab_password_login') }}
                </span>
            </label>
            <label class="flex-1 cursor-pointer text-center" role="tab">
                <input type="radio" name="login_tab" value="sms" class="peer sr-only"
                       onchange="document.getElementById('tab-password').hidden=true;document.getElementById('tab-sms').hidden=false;">
                <span class="block rounded-lg px-3 py-2 text-sm font-medium text-zinc-500 transition peer-checked:bg-white peer-checked:text-zinc-900 peer-checked:shadow-sm">
                    {{ __('auth.tab_sms_login') }}
                </span>
            </label>
        </div>
        @endif

        {{-- Tab 1：邮箱/手机号 + 密码登录 --}}
        <div id="tab-password" @if(! $phoneLoginEnabled) class="mt-8" @else class="mt-6" @endif>
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
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
                           placeholder="\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022"
                           class="form-input @error('password') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if($smsLoginVerifyEnabled ?? false)
                    <div>
                        <label for="sms_code_verify" class="form-label">{{ __('auth.sms_code') }}</label>
                        <div class="flex gap-2">
                            <input id="sms_code_verify" type="text" inputmode="numeric" maxlength="6" name="sms_code" value="{{ old('sms_code') }}" autocomplete="one-time-code"
                                   placeholder="{{ __('auth.sms_code_placeholder') }}" required
                                   class="form-input !w-auto flex-1 @error('sms_code') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                            <button type="submit" form="sms-send-form-verify"
                                    class="whitespace-nowrap rounded-xl border border-brand-600 px-4 py-2 text-sm font-medium text-brand-600 transition hover:bg-brand-50">
                                {{ __('auth.send_sms_code') }}
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ __('auth.sms_code_required_hint') }}
                        </p>
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

                {{-- 人机验证 --}}
@php($captcha = \App\Support\Captcha::widget('login'))
@if ($captcha)
                    <div>
                        {!! $captcha !!}
                        @error('captcha')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
@endif

                <button type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    {{ __('auth.login_btn') }}
                </button>
            </form>

            {{-- 短信二次校验的发码隐藏表单 --}}
            @if($smsLoginVerifyEnabled ?? false)
                <form id="sms-send-form-verify" method="POST" action="{{ route('sms.send') }}" class="hidden">
                    @csrf
                    <input type="hidden" name="purpose" value="login">
                    <input type="hidden" name="phone" id="sms-verify-phone">
                </form>
                <script>
                    document.getElementById('sms-send-form-verify')?.addEventListener('submit', function() {
                        var emailInput = document.getElementById('email');
                        var phoneInput = document.getElementById('sms-verify-phone');
                        if (emailInput && phoneInput) {
                            phoneInput.value = emailInput.value;
                        }
                    });
                </script>
            @endif
        </div>
        {{-- Tab 2：手机验证码登录 --}}
        @if($phoneLoginEnabled)
        <div id="tab-sms" class="mt-6" hidden>
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="phone_login" class="form-label">{{ __('auth.phone') }}</label>
                    <input id="phone_login" type="tel" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="{{ __('auth.phone_placeholder') }}"
                           class="form-input @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sms_code_login" class="form-label">{{ __('auth.sms_code') }}</label>
                    <div class="flex gap-2">
                        <input id="sms_code_login" type="text" inputmode="numeric" maxlength="6" name="sms_code" value="{{ old('sms_code') }}" required autocomplete="one-time-code"
                               placeholder="{{ __('auth.sms_code_placeholder') }}"
                               class="form-input !w-auto flex-1 @error('sms_code') border-red-400 focus:border-red-500 focus:ring-red-500/30 @enderror">
                        <button type="submit" form="sms-send-form-login"
                                class="whitespace-nowrap rounded-xl border border-brand-600 px-4 py-2 text-sm font-medium text-brand-600 transition hover:bg-brand-50">
                            {{ __('auth.send_sms_code') }}
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-zinc-400">
                        {{ __('auth.sms_code_required_hint') }}
                    </p>
                    @error('sms_code')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 隐藏密码字段（不传，走手机号+验证码流程） --}}
                <input type="hidden" name="password" value="">

                {{-- 人机验证 --}}
@php($captcha = \App\Support\Captcha::widget('login'))
@if ($captcha)
                    <div>
                        {!! $captcha !!}
                        @error('captcha')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
@endif

                <button type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    {{ __('auth.login_btn') }}
                </button>
            </form>

            {{-- 发码隐藏表单 --}}
            <form id="sms-send-form-login" method="POST" action="{{ route('sms.send') }}" class="hidden">
                @csrf
                <input type="hidden" name="purpose" value="login">
                <input type="hidden" name="phone" id="sms-login-phone">
            </form>
            <script>
                document.getElementById('sms-send-form-login')?.addEventListener('submit', function() {
                    var phoneInput = document.getElementById('phone_login');
                    var smsPhoneInput = document.getElementById('sms-login-phone');
                    if (phoneInput && smsPhoneInput) {
                        smsPhoneInput.value = phoneInput.value;
                    }
                });
            </script>
        </div>
        @endif
    </div>

    <p class="mt-6 text-sm text-zinc-500">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-brand-600 hover:text-brand-500">{{ __('auth.register_free') }}</a>
    </p>

    @include('partials.social-login')
@endsection
@extends('layouts.guest')

@section('title', __('auth.reset_by_sms'))

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.reset_by_sms') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('auth.reset_by_sms_desc') }}
            </p>
        </div>

        @if(session('status'))
            <div class="rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-800">{{ session('status') }}</p>
            </div>
        @endif

        {{-- 第一步：发送验证码到手机 --}}
        <form method="POST" action="{{ route('sms.send') }}" class="space-y-4 rounded-xl border border-gray-200 bg-white p-4">
            @csrf
            <input type="hidden" name="purpose" value="forgot_password">
            <p class="text-xs font-medium text-gray-500">{{ __('auth.get_sms_code') }}</p>
            <div class="flex gap-2">
                <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                       placeholder="{{ __('auth.phone_placeholder') }}"
                       class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <button type="submit"
                        class="whitespace-nowrap rounded-md border border-blue-600 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50">
                    {{ __('auth.send_sms_code') }}
                </button>
            </div>
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>

        <form class="mt-4 space-y-6" method="POST" action="{{ route('password.reset_sms.post') }}">
            @csrf

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('auth.phone') }}</label>
                <input id="phone" name="phone" type="tel" maxlength="20" required
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="{{ __('auth.phone_placeholder') }}"
                    value="{{ old('phone', $phone ?? '') }}">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sms_code" class="block text-sm font-medium text-gray-700">{{ __('auth.sms_code') }}</label>
                <input id="sms_code" name="sms_code" type="text" inputmode="numeric" maxlength="6" required autocomplete="one-time-code"
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="{{ __('auth.sms_code_placeholder') }}"
                    value="{{ old('sms_code') }}">
                @error('sms_code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('auth.new_password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="{{ __('auth.new_password_placeholder') }}">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('auth.confirm_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ __('auth.reset_password_btn') }}
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-500">
                    {{ __('auth.back_to_login') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

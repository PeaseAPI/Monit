@extends('layouts.guest')

@section('title', __('auth.resend_activation'))

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ __('auth.resend_activation') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ __('auth.resend_activation_desc') }}
            </p>
        </div>

        <form class="mt-8 space-y-6" method="POST" action="{{ route('activation.resend.post') }}">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">{{ __('auth.email') }}</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                    class="mt-1 appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="{{ __('auth.email_placeholder') }}"
                    value="{{ old('email') }}">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ __('auth.resend_activation_btn') }}
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
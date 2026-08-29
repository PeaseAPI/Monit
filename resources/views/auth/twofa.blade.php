@extends('layouts.guest')

@section('title', __('account.twofa_title'))

@section('content')
    <div class="mb-8 md:hidden">
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-xl font-bold text-white">M</span>
    </div>

    <h2 class="text-2xl font-bold">{{ __('account.twofa_title') }}</h2>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.twofa_subtitle') }}</p>

    <form method="POST" action="{{ route('login.twofa.verify') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-zinc-700">{{ __('account.twofa_code_label') }}</label>
            <input id="code" type="text" name="code" inputmode="numeric" pattern="\d{6}" autocomplete="one-time-code"
                   required autofocus placeholder="000000"
                   class="mt-1.5 block w-full rounded-xl border-zinc-300 text-center text-2xl tracking-[0.4em] shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('code') border-red-400 @enderror">
            @error('code')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button class="w-full rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-700">
            {{ __('account.twofa_verify_btn') }}
        </button>

        <a href="{{ route('login') }}" class="block text-center text-sm text-zinc-500 hover:text-zinc-700">{{ __('account.twofa_back_login') }}</a>
    </form>
@endsection

@extends('layouts.guest')

@section('title', __('stats.public_auth_title') . ' - ' . $website->name)

@section('content')
<div class="rounded-3xl border border-zinc-200/70 bg-white p-8 shadow-xl shadow-zinc-900/[0.04]">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/25">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
    </div>
    <h2 class="mt-5 text-center text-xl font-bold tracking-tight text-zinc-900">{{ $website->name }}</h2>
    <p class="mt-2 text-center text-sm text-zinc-500">{{ __('stats.public_auth_desc') }}</p>

    <form class="mt-8 space-y-5" method="POST" action="{{ route('statistics.public.auth', ['pixel_key' => $website->pixel_key]) }}">
        @csrf

        <div>
            <label for="password" class="form-label">{{ __('auth.password') }}</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
            {{ __('auth.view_statistics') }}
        </button>
    </form>
</div>
@endsection

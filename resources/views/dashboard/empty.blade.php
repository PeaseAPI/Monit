@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')
    <div class="mx-auto max-w-2xl py-16 text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-brand-400 to-brand-600 text-2xl font-bold text-white shadow-lg">M</span>
        <h2 class="mt-6 text-2xl font-bold">{{ __('dashboard.welcome_title') }}</h2>
        <p class="mt-3 text-zinc-500">{{ __('dashboard.add_first_website') }}</p>

        <a href="{{ route('websites.create') }}"
           class="mt-8 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            {{ __('dashboard.add_website') }}
        </a>

        <div class="mt-12 grid gap-4 text-left sm:grid-cols-3">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                <p class="text-sm font-medium text-zinc-500">{{ __('dashboard.privacy_first') }}</p>
                <p class="mt-1 text-sm text-zinc-600">{{ __('dashboard.privacy_first_desc') }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                <p class="text-sm font-medium text-zinc-500">{{ __('dashboard.lightweight_pixel') }}</p>
                <p class="mt-1 text-sm text-zinc-600">{{ __('dashboard.lightweight_pixel_desc') }}</p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                <p class="text-sm font-medium text-zinc-500">{{ __('dashboard.self_hosted') }}</p>
                <p class="mt-1 text-sm text-zinc-600">{{ __('dashboard.self_hosted_desc') }}</p>
            </div>
        </div>
    </div>
@endsection

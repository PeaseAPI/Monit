@extends('layouts.public')
@section('title', __('seo.landing_title'))
@section('meta_description', __('seo.landing_description'))
@section('content')
<div>
    <div class="mx-auto max-w-2xl py-16">
        <div class="text-center">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ $testCount }} {{ __('seo.landing_tests_available') }}
            </span>
            <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">{{ __('seo.landing_header') }}</h1>
            <p class="mx-auto mt-4 text-lg text-zinc-500">{{ __('seo.landing_subheader') }}</p>
        </div>

        <form method="POST" action="{{ route('seo.analyze') }}" class="mt-8 flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-5 sm:flex-row">
            @csrf
            <input type="text" name="url" required placeholder="example.com" value="{{ old('url') }}"
                   class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm" autofocus>
            <button type="submit" class="rounded-lg bg-zinc-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-zinc-800">{{ __('seo.free_analyze') }}</button>
        </form>
        @error('url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

        <div class="mt-10 rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-zinc-900">{{ __('seo.landing_features') }}</h2>
            <ul class="mt-3 grid gap-2 text-sm text-zinc-500 sm:grid-cols-2">
                <li class="flex items-center gap-2">🔍 {{ __('seo.landing_feature_audit_title') }}</li>
                <li class="flex items-center gap-2">📊 {{ __('seo.landing_feature_score_title') }}</li>
                <li class="flex items-center gap-2">🤖 {{ __('seo.landing_feature_ai_title') }}</li>
                <li class="flex items-center gap-2">🔗 {{ __('seo.landing_feature_backlinks_title') }}</li>
            </ul>
            <p class="mt-4 text-xs text-zinc-400">
                <a href="{{ route('seo.landing') }}" class="hover:underline">{{ __('seo.landing_header') }} &rarr;</a>
            </p>
        </div>
    </div>
</div>
@endsection

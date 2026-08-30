@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.preferences_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.preferences_desc') }}</p>

    <form method="POST" action="{{ route('account.preferences.update') }}" class="mt-6 max-w-xl">
        @csrf @method('PUT')
        <div class="space-y-5">
            {{-- 主题 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('account.theme') }}</label>
                <select name="theme" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
                    <option value="auto" {{ ($preferences['theme'] ?? 'auto') === 'auto' ? 'selected' : '' }}>{{ __('account.theme_auto') }}</option>
                    <option value="light" {{ ($preferences['theme'] ?? '') === 'light' ? 'selected' : '' }}>{{ __('account.theme_light') }}</option>
                    <option value="dark" {{ ($preferences['theme'] ?? '') === 'dark' ? 'selected' : '' }}>{{ __('account.theme_dark') }}</option>
                </select>
            </div>

            {{-- 语言 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('account.language') }}</label>
                <select name="language" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
                    @foreach($languages as $code => $name)
                    <option value="{{ $code }}" {{ $user->language === $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 时区 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('account.timezone') }}</label>
                <select name="timezone" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
                    @foreach($timezones as $tz)
                    <option value="{{ $tz }}" {{ $user->timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 统计默认范围 --}}
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('account.stats_default_range') }}</label>
                <select name="stats_default_range" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
                    <option value="24h" {{ ($preferences['stats_default_range'] ?? '') === '24h' ? 'selected' : '' }}>24h</option>
                    <option value="7d" {{ ($preferences['stats_default_range'] ?? '') === '7d' ? 'selected' : '' }}>7d</option>
                    <option value="30d" {{ ($preferences['stats_default_range'] ?? '30d') === '30d' ? 'selected' : '' }}>30d</option>
                    <option value="90d" {{ ($preferences['stats_default_range'] ?? '') === '90d' ? 'selected' : '' }}>90d</option>
                    <option value="12m" {{ ($preferences['stats_default_range'] ?? '') === '12m' ? 'selected' : '' }}>12m</option>
                </select>
            </div>

            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                {{ __('account.save_preferences') }}
            </button>
        </div>
    </form>
</div>
@endsection
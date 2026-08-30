@extends('layouts.guest')
@section('title', __('affiliate.title'))
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <div class="text-center">
        <h1 class="text-3xl font-bold text-zinc-900">{{ __('affiliate.title') }}</h1>
        <p class="mt-3 text-zinc-600">{{ __('affiliate.subtitle') }}</p>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center">
            <div class="text-3xl font-bold text-brand-600">{{ $commission }}%</div>
            <p class="mt-2 text-sm text-zinc-600">{{ __('affiliate.commission_rate') }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center">
            <div class="text-3xl font-bold text-brand-600">{{ $cookieDays }}</div>
            <p class="mt-2 text-sm text-zinc-600">{{ __('affiliate.cookie_days') }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center">
            <div class="text-3xl font-bold text-brand-600">{{ $minWithdrawal }}</div>
            <p class="mt-2 text-sm text-zinc-600">{{ __('affiliate.min_withdrawal') }}</p>
        </div>
    </div>

    <div class="mt-10 space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('affiliate.how_it_works') }}</h2>
            <ol class="mt-4 list-decimal space-y-3 pl-5 text-sm text-zinc-600">
                <li>{{ __('affiliate.step_1') }}</li>
                <li>{{ __('affiliate.step_2') }}</li>
                <li>{{ __('affiliate.step_3') }}</li>
            </ol>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('affiliate.payouts') }}</h2>
            <p class="mt-2 text-sm text-zinc-600">{{ __('affiliate.payouts_desc') }}</p>
        </div>
    </div>

    <div class="mt-10 text-center">
        @guest
            <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('affiliate.join_now') }}
            </a>
        @else
            <a href="{{ route('referrals.index') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('affiliate.go_to_dashboard') }}
            </a>
        @endguest
    </div>
</div>
@endsection

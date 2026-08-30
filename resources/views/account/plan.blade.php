@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.plan_title') }}</h1>

    {{-- 当前套餐 --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.current_plan') }}</h3>
        <div class="mt-3 flex items-center gap-4">
            <div class="rounded-xl bg-brand-50 px-4 py-2 text-lg font-bold text-brand-700">
                {{ $currentPlan?->name ?? __('account.free_plan') }}
            </div>
            @if($user->plan_expiration_date)
            <span class="text-sm text-zinc-500">
                {{ __('account.expires') }}: {{ $user->plan_expiration_date->format('Y-m-d') }}
            </span>
            @endif
        </div>
    </div>

    {{-- 兑换码 --}}
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.redeem_code') }}</h3>
        <form method="POST" action="{{ route('account.plan.redeem') }}" class="mt-3 flex gap-3 max-w-md">
            @csrf
            <input type="text" name="code" placeholder="{{ __('account.enter_code') }}" class="flex-1 rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.redeem') }}</button>
        </form>
    </div>

    {{-- 套餐列表 --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($plans as $plan)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 {{ $user->plan_id === $plan->plan_id ? 'ring-2 ring-brand-600' : '' }}">
            <h4 class="font-semibold text-zinc-900">{{ $plan->name }}</h4>
            <p class="mt-1 text-2xl font-bold text-brand-600">
                @json($plan->prices)
            </p>
            @if($user->plan_id !== $plan->plan_id)
            <a href="{{ route('payments.index', ['plan' => $plan->plan_id]) }}" class="mt-3 inline-block rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
                {{ __('account.upgrade') }}
            </a>
            @else
            <span class="mt-3 inline-block rounded-xl bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-500">{{ __('account.current') }}</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
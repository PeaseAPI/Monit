@extends('layouts.app')
@section('title', __('referrals.title'))

@section('content')
<div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('referrals.title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('referrals.subtitle') }}</p>

    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-700">{{ __('referrals.code') }}</p>
        <code class="mt-2 block rounded-lg bg-zinc-100 p-3 text-xs text-zinc-600">{{ $referralKey ?? '-' }}</code>
        <p class="mt-4 text-sm font-medium text-zinc-700">{{ __('referrals.link') }}</p>
        <code class="mt-2 block rounded-lg bg-zinc-100 p-3 text-xs text-zinc-600 break-all">{{ $referralUrl ?? '-' }}</code>
    </div>

    {{-- 佣金余额与提现 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('referrals.commission_balance') }}</h3>
        <p class="mt-2 text-2xl font-bold text-brand-600">{{ $commissionBalance ?? '0.00' }} USD</p>
        <form method="POST" action="{{ route('referrals.withdrawal') }}" class="mt-4">@csrf
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('referrals.withdrawal_amount') }}</label>
                <input type="number" name="amount" step="0.01" min="1" required placeholder="0.00" class="form-input">
            </div>
            <button class="mt-3 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('referrals.request_withdrawal') }}</button>
        </form>
    </div>

    {{-- 提现记录 --}}
    <div class="mt-6 max-w-xl">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-zinc-900">{{ __('referrals.withdrawals') }}</h3>
            <a href="{{ route('referrals.withdrawals') }}" class="text-sm text-brand-600 hover:underline">{{ __('referrals.view_all') }}</a>
        </div>
        <div class="mt-3 space-y-2">
            @forelse($recentWithdrawals ?? [] as $w)
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-900">{{ $w->amount }} USD</p>
                    <p class="mt-1 text-xs text-zinc-400">{{ $w->datetime->format('Y-m-d H:i') }}</p>
                </div>
                <span class="rounded-lg px-2 py-0.5 text-xs font-medium {{ $w->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($w->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ __('referrals.withdrawal_status_' . $w->status) }}</span>
            </div>
            @empty
            <p class="text-sm text-zinc-400">{{ __('referrals.no_withdrawals') }}</p>
            @endforelse
        </div>
    </div>

    {{-- 推荐记录 --}}
    <div class="mt-6 max-w-xl">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('referrals.records') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse($referrals ?? [] as $ref)
            <div class="rounded-2xl border border-zinc-200 bg-white p-4"><p class="text-sm text-zinc-700">{{ $ref->name ?? $ref->email ?? '-' }}</p><p class="mt-1 text-xs text-zinc-400">{{ $ref->datetime ?? '-' }}</p></div>
            @empty<p class="text-sm text-zinc-400">{{ __('referrals.no_records') }}</p>@endforelse
        </div>
    </div>
</div>
@endsection
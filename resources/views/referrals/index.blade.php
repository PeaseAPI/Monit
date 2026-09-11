@extends('layouts.app')
@section('title', __('referrals.title'))

@section('content')
<div class="mx-auto max-w-5xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('referrals.title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('referrals.subtitle') }}</p>

    {{-- 顶部双栏：推荐信息 | 佣金与提现 --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h3 class="text-sm font-semibold text-zinc-900">{{ __('referrals.code') }}</h3>
            <div class="mt-3 flex items-center gap-2">
                <code class="min-w-0 flex-1 truncate rounded-lg bg-zinc-100 px-3 py-2.5 text-xs text-zinc-600">{{ $referralKey ?? '-' }}</code>
                <button type="button" data-copy="{{ $referralKey ?? '' }}"
                        class="shrink-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs font-medium text-zinc-600 transition hover:border-brand-400 hover:text-brand-600">{{ __('referrals.copy') }}</button>
            </div>
            <p class="mt-5 text-sm font-medium text-zinc-700">{{ __('referrals.link') }}</p>
            <div class="mt-3 flex items-center gap-2">
                <code class="min-w-0 flex-1 truncate rounded-lg bg-zinc-100 px-3 py-2.5 text-xs break-all text-zinc-600">{{ $referralUrl ?? '-' }}</code>
                <button type="button" data-copy="{{ $referralUrl ?? '' }}"
                        class="shrink-0 rounded-lg border border-zinc-200 px-3 py-2 text-xs font-medium text-zinc-600 transition hover:border-brand-400 hover:text-brand-600">{{ __('referrals.copy') }}</button>
            </div>
        </div>

        <div class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-6">
            <h3 class="text-sm font-semibold text-zinc-900">{{ __('referrals.commission_balance') }}</h3>
            <p class="mt-2 text-3xl font-bold tracking-tight text-brand-600">{{ \App\Support\Currency::format((float) ($commissionBalance ?? 0)) }}</p>
            <form method="POST" action="{{ route('referrals.withdrawal') }}" class="mt-auto flex items-end gap-2 pt-5">@csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-500">{{ __('referrals.withdrawal_amount') }}</label>
                    <input type="number" name="amount" step="0.01" min="1" required placeholder="0.00" class="form-input mt-1">
                </div>
                <button class="whitespace-nowrap rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('referrals.request_withdrawal') }}</button>
            </form>
        </div>
    </div>

    {{-- 下方双栏：推荐记录 | 提现记录 --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <div>
            <h2 class="text-base font-semibold text-zinc-900">{{ __('referrals.records') }}</h2>
            <div class="mt-3 space-y-2">
                @forelse($referrals ?? [] as $ref)
                <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">{{ mb_substr(strval($ref->name ?? $ref->email ?? '?'), 0, 1) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm text-zinc-700">{{ $ref->name ?? $ref->email ?? '-' }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400">{{ $ref->datetime ?? '-' }}</p>
                    </div>
                </div>
                @empty<p class="text-sm text-zinc-400">{{ __('referrals.no_records') }}</p>@endforelse
            </div>
        </div>
        <div>
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900">{{ __('referrals.withdrawals') }}</h3>
                <a href="{{ route('referrals.withdrawals') }}" class="text-sm text-brand-600 hover:underline">{{ __('referrals.view_all') }}</a>
            </div>
            <div class="mt-3 space-y-2">
                @forelse($recentWithdrawals ?? [] as $w)
                <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-white p-4">
                    <div>
                        <p class="text-sm font-medium text-zinc-900">{{ \App\Support\Currency::format((float) $w->amount) }}</p>
                        <p class="mt-0.5 text-xs text-zinc-400">{{ $w->datetime->format('Y-m-d H:i') }}</p>
                    </div>
                    <span class="rounded-lg px-2 py-0.5 text-xs font-medium {{ $w->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($w->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">{{ __('referrals.withdrawal_status_' . $w->status) }}</span>
                </div>
                @empty
                <p class="text-sm text-zinc-400">{{ __('referrals.no_withdrawals') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('button[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var original = btn.textContent;
                navigator.clipboard.writeText(btn.dataset.copy || '');
                btn.textContent = '✓';
                setTimeout(function () { btn.textContent = original; }, 1200);
            });
        });
    })();
</script>
@endsection
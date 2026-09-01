@extends('layouts.app')
@section('title', __('referrals.withdrawals'))

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('referrals.withdrawals') }}</h1>
        <a href="{{ route('referrals.index') }}" class="text-sm text-brand-600 hover:underline">{{ __('referrals.back_to_referrals') }}</a>
    </div>

    <div class="mt-6 space-y-3">
        @forelse($withdrawals as $w)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 flex items-center justify-between">
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

    {{ $withdrawals->links() }}
</div>
@endsection
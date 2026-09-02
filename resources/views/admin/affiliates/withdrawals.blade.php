@extends('layouts.admin')
@section('title', __('admin.affiliate_withdrawals'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.affiliate_withdrawals') }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.affiliates-withdrawals.index') }}?status=pending" class="rounded-xl px-3 py-1.5 text-sm {{ request('status') === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-zinc-100 text-zinc-600' }}">{{ __('referrals.withdrawal_status_pending') }}</a>
            <a href="{{ route('admin.affiliates-withdrawals.index') }}?status=approved" class="rounded-xl px-3 py-1.5 text-sm {{ request('status') === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }}">{{ __('referrals.withdrawal_status_completed') }}</a>
            <a href="{{ route('admin.affiliates-withdrawals.index') }}?status=rejected" class="rounded-xl px-3 py-1.5 text-sm {{ request('status') === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-zinc-100 text-zinc-600' }}">{{ __('referrals.withdrawal_status_rejected') }}</a>
                        <a href="{{ route('admin.affiliates-withdrawals.index') }}" class="rounded-xl px-3 py-1.5 text-sm {{ !request('status') ? 'bg-brand-100 text-brand-700' : 'bg-zinc-100 text-zinc-600' }}">{{ __('admin.all') }}</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('admin.col_user') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('admin.amount') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('admin.col_register_time') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('common.status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($withdrawals as $w)
                <tr class="hover:bg-zinc-50">
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900">{{ $w->affiliate_withdrawal_id }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900">{{ $w->user->name ?? '-' }}<br><span class="text-xs text-zinc-400">{{ $w->user->email ?? '-' }}</span></td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-zinc-900">{{ $w->amount }} {{ $w->currency }}</td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500">{{ $w->datetime->format('Y-m-d H:i') }}</td>
                    <td class="whitespace-nowrap px-6 py-4">
                        @php $statusClass = match($w->status) { 'approved' => 'bg-emerald-100 text-emerald-700', 'rejected' => 'bg-red-100 text-red-700', default => 'bg-amber-100 text-amber-700' }; @endphp
                        <span class="rounded-lg px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ __('referrals.withdrawal_status_' . $w->status) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                        @if($w->status === 'pending')
                        <form method="POST" action="{{ route('admin.affiliates-withdrawals.approve', $w->affiliate_withdrawal_id) }}" class="inline">@csrf @method('PUT')
                            <button class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-medium text-white hover:bg-emerald-700">{{ __('admin.approve') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.affiliates-withdrawals.reject', $w->affiliate_withdrawal_id) }}" class="inline">@csrf @method('PUT')
                            <button class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">{{ __('admin.reject') }}</button>
                        </form>
                        @else
                        <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-400">{{ __('common.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $withdrawals->links() }}
</div>
@endsection
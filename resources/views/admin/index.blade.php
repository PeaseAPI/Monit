@extends('layouts.admin')

@section('title', __('admin.overview'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.overview') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.system_overview') }}</p>
</div>
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.total_users') }}</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900">{{ $totalUsers ?? 0 }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.total_websites') }}</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900">{{ $totalWebsites ?? 0 }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.payment_records') }}</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900">{{ $totalPayments ?? 0 }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.monthly_revenue') }}</p>
        <p class="mt-2 text-3xl font-bold text-emerald-600">¥{{ number_format($monthlyRevenue ?? 0, 2) }}</p>
    </div>
</div>
<div class="mt-8 rounded-2xl border border-zinc-200 bg-white">
    <div class="border-b border-zinc-200 px-6 py-4"><h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.recent_users') }}</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_user') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_email') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_register_time') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($recentUsers ?? [] as $user)
                <tr>
                    <td class="px-6 py-3">{{ $user->name }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $user->email }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $user->datetime }}</td>
                </tr>
                @empty
                <tr><td class="px-6 py-8 text-center text-zinc-500" colspan="3">{{ __('admin.no_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
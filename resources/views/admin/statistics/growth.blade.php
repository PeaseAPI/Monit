@extends('layouts.admin')
@section('title', __('admin.statistics_growth'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_growth') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_growth_desc') }}</p>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-medium text-zinc-500">{{ __('admin.user_growth') }}</h3>
        <div class="mt-4 space-y-2">
            @foreach($userGrowth as $point)
            <div class="flex items-center justify-between text-xs">
                <span class="text-zinc-500">{{ $point['date'] }}</span>
                <span class="font-mono font-medium">{{ number_format($point['count']) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-medium text-zinc-500">{{ __('admin.website_growth') }}</h3>
        <div class="mt-4 space-y-2">
            @foreach($websiteGrowth as $point)
            <div class="flex items-center justify-between text-xs">
                <span class="text-zinc-500">{{ $point['date'] }}</span>
                <span class="font-mono font-medium">{{ number_format($point['count']) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-medium text-zinc-500">{{ __('admin.payment_growth') }}</h3>
        <div class="mt-4 space-y-2">
            @foreach($paymentGrowth as $point)
            <div class="flex items-center justify-between text-xs">
                <span class="text-zinc-500">{{ $point['date'] }}</span>
                <span class="font-mono font-medium">{{ number_format($point['count']) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

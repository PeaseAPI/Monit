@extends('layouts.admin')
@section('title', __('admin.statistics_users'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_users') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_users_desc') }}</p>
</div>
<div class="mb-4 flex gap-2">
    <a href="{{ route('admin.statistics.users', ['period' => '7d']) }}" class="rounded-lg px-3 py-1.5 text-sm {{ request('period', '30d') === '7d' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-700' }}">7d</a>
    <a href="{{ route('admin.statistics.users', ['period' => '30d']) }}" class="rounded-lg px-3 py-1.5 text-sm {{ request('period', '30d') === '30d' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-700' }}">30d</a>
    <a href="{{ route('admin.statistics.users', ['period' => '90d']) }}" class="rounded-lg px-3 py-1.5 text-sm {{ request('period', '30d') === '90d' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-700' }}">90d</a>
</div>
<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-lg font-semibold text-zinc-900">{{ __('admin.new_users_trend') }}</h3>
        <div class="mt-4 space-y-1">
            @foreach($newUsers as $point)
            <div class="flex items-center justify-between text-xs">
                <span class="text-zinc-500">{{ $point['date'] }}</span>
                <span class="font-mono font-medium">{{ number_format($point['count']) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h3 class="text-lg font-semibold text-zinc-900">{{ __('admin.users_by_source') }}</h3>
            <div class="mt-4 space-y-1">
                @foreach($bySource as $row)
                <div class="flex items-center justify-between text-sm">
                    <span>{{ $row->source ?? __('admin.unknown') }}</span>
                    <span class="font-mono">{{ number_format($row->count) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h3 class="text-lg font-semibold text-zinc-900">{{ __('admin.users_by_country') }}</h3>
            <div class="mt-4 space-y-1">
                @foreach($byCountry as $row)
                <div class="flex items-center justify-between text-sm">
                    <span>{{ $row->country ?? __('admin.unknown') }}</span>
                    <span class="font-mono">{{ number_format($row->count) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

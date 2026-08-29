@extends('layouts.admin')
@section('title', __('admin.statistics_title'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_title') }}</h1><p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_subtitle') }}</p></div>
<div class="grid gap-6 md:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.total_visits') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($totalVisits ?? 0) }}</p></div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.daily_avg_visits') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($dailyAvg ?? 0) }}</p></div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6"><p class="text-sm font-medium text-zinc-500">{{ __('admin.total_events') }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($totalEvents ?? 0) }}</p></div>
</div>
@endsection
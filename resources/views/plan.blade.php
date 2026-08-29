@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-7xl px-6 py-12">
    <h1 class="text-3xl font-bold text-center text-zinc-900">{{ __('plan.title') }}</h1>
    <div class="mt-12 grid gap-6 md:grid-cols-3">
        @forelse($plans ?? [] as $plan)
        <div class="rounded-2xl border border-zinc-200 bg-white p-8 text-center">
            <h2 class="text-xl font-bold">{{ $plan->name }}</h2>
            <p class="mt-4 text-4xl font-bold">¥{{ number_format($plan->price, 2) }}</p>
            <ul class="mt-6 space-y-2 text-sm text-zinc-600"><li>{{ __('plan.max_websites', ['count' => $plan->max_websites ?? '∞']) }}</li></ul>
            <a href="{{ route('register') }}" class="mt-6 inline-block rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('landing.get_started') }}</a>
        </div>
        @empty<p class="text-zinc-500">{{ __('common.no_plans') }}</p>@endforelse
    </div>
</div>
@endsection
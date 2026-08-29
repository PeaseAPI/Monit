@extends('layouts.guest')
@section('content')
<section class="mx-auto max-w-7xl px-6 py-20 text-center">
    <h1 class="text-4xl font-bold text-zinc-900 md:text-5xl">{{ __('landing.title') }}</h1>
    <p class="mt-4 text-lg text-zinc-500">{{ __('landing.subtitle') }}</p>
    <div class="mt-8 flex justify-center gap-4">
        <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-medium text-white hover:bg-brand-700">{{ __('landing.get_started') }}</a>
        <a href="{{ route('plan') }}" class="rounded-xl border border-zinc-300 px-6 py-3 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('landing.view_pricing') }}</a>
    </div>
</section>
<section class="mx-auto max-w-7xl px-6 py-16">
    <h2 class="text-2xl font-bold text-center text-zinc-900">{{ __('landing.plans') }}</h2>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        @forelse($plans ?? [] as $plan)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center"><h3 class="text-lg font-semibold">{{ $plan->name }}</h3><p class="mt-2 text-3xl font-bold">¥{{ number_format($plan->price, 2) }}</p></div>
        @empty<p class="text-zinc-500">{{ __('common.no_plans') }}</p>@endforelse
    </div>
</section>
@endsection
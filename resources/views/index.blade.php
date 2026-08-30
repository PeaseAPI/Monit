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
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-zinc-900">{{ __('landing.plans') }}</h2>
        {{-- 货币切换器（规格书 §6.1：定价卡 + 货币切换） --}}
        <form method="GET" action="{{ route('index') }}" class="flex items-center gap-2">
            <label for="landing-currency" class="text-sm text-zinc-500">{{ __('landing.currency') }}</label>
            <select id="landing-currency" name="currency" onchange="this.form.submit()"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-700">
                @foreach ($currencies ?? [] as $code => $meta)
                    <option value="{{ $code }}" @selected($code === ($currency ?? 'CNY'))>{{ $code }} {{ $meta['symbol'] ?? '' }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="mt-8 grid gap-6 md:grid-cols-3">
        @forelse($plans ?? [] as $plan)
        @php($symbol = $currencies[$plan->landing_currency ?? ($currency ?? 'CNY')]['symbol'] ?? '¥')
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 text-center">
            <h3 class="text-lg font-semibold">{{ $plan->name }}</h3>
            <p class="mt-2 text-3xl font-bold">
                @if ($plan->landing_price !== null)
                    {{ $symbol }}{{ number_format((float) $plan->landing_price, 2) }}<span class="text-sm font-normal text-zinc-400">/{{ __('landing.per_month') }}</span>
                @else
                    {{ __('common.no_plans') }}
                @endif
            </p>
        </div>
        @empty<p class="text-zinc-500">{{ __('common.no_plans') }}</p>@endforelse
    </div>
</section>
@endsection
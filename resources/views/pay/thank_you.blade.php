@extends('layouts.app')
@section('content')
<div class="p-8 max-w-2xl text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
        <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
    </div>
    <h1 class="mt-4 text-2xl font-bold text-zinc-900">{{ __('pay.thank_you_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('pay.thank_you_desc') }}</p>

    @if($plan)
    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 text-left">
        <div class="space-y-2 text-sm">
            <p class="text-zinc-500">{{ __('pay.plan') }}: <span class="font-medium text-zinc-900">{{ $plan->name }}</span></p>
        </div>
    </div>
    @endif

    <div class="mt-6 flex justify-center gap-3">
        <a href="{{ route('dashboard') }}" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
            {{ __('pay.go_dashboard') }}
        </a>
        <a href="{{ route('invoices.index') }}" class="rounded-xl border border-zinc-300 px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
            {{ __('pay.view_invoices') }}
        </a>
    </div>
</div>
@endsection
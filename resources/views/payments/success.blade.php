@extends('layouts.app')
@section('title', __('payments.success_title'))

@section('content')
<div class="p-8 max-w-xl">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center">
        <svg class="mx-auto h-16 w-16 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <h1 class="mt-4 text-2xl font-bold text-emerald-900">{{ __('payments.success_title') }}</h1>
        <p class="mt-2 text-sm text-emerald-700">{{ __('payments.success_desc') }}</p>
        <a href="{{ route('payments.index') }}" class="mt-6 inline-block rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('payments.back_to_payments') }}</a>
    </div>
</div>
@endsection
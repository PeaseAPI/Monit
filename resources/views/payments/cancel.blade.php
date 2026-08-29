@extends('layouts.app')
@section('title', __('payments.cancel_title'))

@section('content')
<div class="p-8 max-w-xl">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
        <svg class="mx-auto h-16 w-16 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <h1 class="mt-4 text-2xl font-bold text-amber-900">{{ __('payments.cancel_title') }}</h1>
        <p class="mt-2 text-sm text-amber-700">{{ __('payments.cancel_desc') }}</p>
        <a href="{{ route('payments.index') }}" class="mt-6 inline-block rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('payments.back_to_payments') }}</a>
    </div>
</div>
@endsection
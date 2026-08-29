@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-xl px-6 py-20 text-center">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100">
        <svg class="h-8 w-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h1 class="mt-6 text-3xl font-bold text-zinc-900">{{ __('msg.unsubscribed') }}</h1>
    <p class="mt-3 text-zinc-500">{{ $email }}</p>
    @if($already)<p class="mt-1 text-sm text-zinc-400">{{ __('msg.unsubscribed') }}</p>@endif
    <a href="{{ route('index') }}" class="mt-8 inline-block rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.back') }}</a>
</div>
@endsection
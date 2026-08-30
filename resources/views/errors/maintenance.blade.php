@extends('layouts.guest')
@section('content')
<div class="flex min-h-[60vh] flex-col items-center justify-center text-center px-6">
    <div class="mb-8">
        <svg class="mx-auto h-24 w-24 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
    </div>
    <h2 class="text-2xl font-bold text-zinc-900">{{ __('errors.maintenance_title') }}</h2>
    <p class="mt-2 max-w-md text-zinc-500">{{ __('errors.maintenance_desc') }}</p>
    <p class="mt-4 text-sm text-zinc-400">{{ __('errors.maintenance_retry') }}</p>
</div>
@endsection

@extends('layouts.guest')
@section('content')
<div class="flex min-h-[60vh] flex-col items-center justify-center text-center px-6">
    <h1 class="text-9xl font-bold text-zinc-300">500</h1>
    <h2 class="mt-4 text-2xl font-bold text-zinc-900">{{ __('errors.500_title') }}</h2>
    <p class="mt-2 text-zinc-500">{{ __('errors.500_desc') }}</p>
    <div class="mt-8 flex gap-4">
        <a href="{{ route('index') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-medium text-white hover:bg-brand-700">{{ __('errors.back_home') }}</a>
    </div>
</div>
@endsection

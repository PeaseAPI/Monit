@extends('layouts.app', ['nav' => 'domains'])
@section('title', __('domains.add_domain'))
@section('content')
<div class="max-w-7xl">
    <div class="mb-6"><a href="{{ route('domains.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('domains.add_domain') }}</h1></div>
    <div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('domains.store') }}">@csrf
        <div class="space-y-4">
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('domains.domain_label') }}</label><input type="text" name="host" placeholder="example.com" class="form-input" required></div>
            <p class="text-xs text-zinc-400">{{ __('domains.monitor_hint') }}</p>
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
        </div></form>
    </div>
</div>
@endsection
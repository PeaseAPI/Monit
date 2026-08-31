@extends('layouts.guest')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.locked_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('seo.report_locked') }}</p>

    <form method="POST" action="{{ route('seo.audits.password', $audit->seo_audit_id) }}" class="mt-6 flex max-w-md gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
        @csrf
        <input type="password" name="password" required placeholder="{{ __('seo.enter_password') }}"
               class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.unlock') }}</button>
    </form>
    @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
@endsection

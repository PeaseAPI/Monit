@extends('layouts.admin')
@section('title', __('admin.settings_title'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.settings_title') }}</h1></div>
<form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.site_name') }}</label><input type="text" name="site_name" value="{{ $settings['site_name'] ?? 'Monit' }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ 'Notification Email' }}</label><input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.save') }}</button>
    </div>
</form>
@endsection
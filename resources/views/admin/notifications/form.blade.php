@extends('layouts.admin')
@section('title', __('admin.notification_create'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.notification_create') }}</h1></div>
<form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
    @csrf
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.title') }}</label>
        <input type="text" name="title" value="{{ old('title') }}" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.content') }}</label>
        <textarea name="message" rows="6" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('message') }}</textarea></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.notification_target') }}</label>
        <input type="email" name="target_email" value="{{ old('target_email') }}" placeholder="{{ __('admin.notification_target_hint') }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
    <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.notification_send') }}</button>
</form>
@endsection
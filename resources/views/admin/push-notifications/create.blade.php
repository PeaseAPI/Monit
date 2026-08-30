@extends('layouts.admin')
@section('title', __('admin.push_campaigns'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.push-notifications.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_campaign') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.push-notifications.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.campaign_name') }}</label><input type="text" name="name" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.campaign_title') }}</label><input type="text" name="title" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.campaign_body') }}</label><textarea name="body" rows="4" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.campaign_url') }}</label><input type="url" name="url" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" placeholder="https://"></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
@endsection
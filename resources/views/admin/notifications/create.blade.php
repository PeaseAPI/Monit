@extends('layouts.admin')
@section('title', __('admin.notifications'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.notifications.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_notification') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.notifications.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.notification_title') }}</label><input type="text" name="title" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.notification_message') }}</label><textarea name="message" rows="4" class="form-input" required></textarea></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.notification_type') }}</label>
            <select name="type" class="form-input"><option value="info">Info</option><option value="success">Success</option><option value="warning">Warning</option><option value="danger">Danger</option></select>
        </div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
@endsection
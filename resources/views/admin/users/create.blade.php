@extends('layouts.admin')
@section('title', __('admin.create_user'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_user') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.users.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.user_name') }}</label><input type="text" name="name" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('contact.email_label') }}</label><input type="email" name="email" class="form-input" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.user_password') }}</label><input type="password" name="password" class="form-input" required></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div></form>
</div>
@endsection
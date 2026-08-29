@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-2xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('contact.title') }}</h1>
    @if(session('success'))<div class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('contact.send') }}" class="mt-6 space-y-4 rounded-2xl border border-zinc-200 bg-white p-6">
        @csrf
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('contact.name_label') }}</label><input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('contact.email_label') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('contact.message_label') }}</label><textarea name="message" rows="4" required class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">{{ old('message') }}</textarea></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('contact.send') }}</button>
    </form>
</div>
@endsection
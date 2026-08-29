@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="mb-6"><a href="{{ route('stats.annotations', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_annotations') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.create_annotation_title') }} - {{ $website->name }}</h1></div>
    <div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('stats.annotations.store') }}">@csrf
        <input type="hidden" name="website_id" value="{{ $website->website_id }}">
        <div class="space-y-4">
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.annotation_name') }}</label><input type="text" name="name" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.annotation_date_label') }}</label><input type="date" name="date" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('stats.add') }}</button>
        </div></form>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6"><a href="{{ route('stats.heatmaps', $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('stats.back_to_heatmaps') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('stats.create_heatmap_title') }} - {{ $website->name }}</h1></div>
    <div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('stats.heatmaps.store') }}">@csrf
        <input type="hidden" name="website_id" value="{{ $website->website_id }}">
        <div class="space-y-4">
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.heatmap_name_label') }}</label><input type="text" name="name" class="form-input" required></div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('stats.heatmap_path_label') }}</label><input type="text" name="path" placeholder="/homepage" class="form-input" required></div>
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('stats.create') }}</button>
        </div></form>
    </div>
</div>
@endsection
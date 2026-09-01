@extends('layouts.public')
@section('title', __('seo.tools_title'))
@section('content')
<div>
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.tools_title') }}</h1>

    @foreach($categories as $category => $tools)
        <div class="mt-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __("seo.category_{$category}") }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($tools as $slug => $meta)
                    <a href="{{ route('seo.tools.show', $slug) }}"
                       class="rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-400">
                        <div class="font-medium text-zinc-900">{{ $meta['name'] ?? \Illuminate\Support\Str::headline($slug) }}</div>
                        <div class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $meta['description'] ?? '' }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection

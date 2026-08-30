@extends('layouts.guest')

@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('pages.title') }}</h1>
    <div class="mt-8 space-y-6">
        @forelse ($pages as $page)
        <a href="{{ route('page', $page->url) }}" class="block rounded-2xl border border-zinc-200 bg-white p-6 hover:border-zinc-300">
            <h2 class="text-lg font-semibold">{{ $page->title }}</h2>
            @if ($page->description)
                <p class="mt-1 text-sm text-zinc-500">{{ $page->description }}</p>
            @endif
        </a>
        @empty
        <p class="text-zinc-500">{{ __('common.no_articles') }}</p>
        @endforelse
    </div>
</div>
@endsection

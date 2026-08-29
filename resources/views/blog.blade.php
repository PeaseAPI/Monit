@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('blog.title') }}</h1>
    <div class="mt-8 space-y-6">
        @forelse($posts ?? [] as $post)
        <a href="{{ route('blog.post', $post->url) }}" class="block rounded-2xl border border-zinc-200 bg-white p-6 hover:border-zinc-300"><h2 class="text-lg font-semibold">{{ $post->title }}</h2><p class="mt-1 text-sm text-zinc-500">{{ $post->datetime }}</p></a>
        @empty<p class="text-zinc-500">{{ __('common.no_articles') }}</p>@endforelse
    </div>
</div>
@endsection
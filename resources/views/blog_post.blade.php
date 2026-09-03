@extends('layouts.guest')
@section('title', $post->title)
@section('meta_description', $post->description ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->content)), 157))
@section('canonical', route('blog.post', $post->url))
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <a href="{{ route('blog') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('blog.back_to_blog') }}</a>
    <h1 class="mt-4 text-3xl font-bold text-zinc-900">{{ $post->title }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ $post->datetime }}</p>
    <div class="mt-6 prose text-zinc-700">{!! $post->content !!}</div>
</div>
@endsection
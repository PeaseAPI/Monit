@extends('layouts.public')
@section('main_class', 'w-full')
@section('title', $post->title)
@section('meta_description', $post->description ?: \Illuminate\Support\Str::limit(trim(strip_tags($post->content)), 157))
@section('canonical', route('blog.post', $post->url))
@section('content')
@php
    $postCategory = $post->category;
    /* 阅读时长估算：按正文字数 400 字/分钟，忽略 HTML 标签 */
    $readMinutes = max(1, (int) ceil(mb_strlen(trim(strip_tags($post->content))) / 400));
@endphp
<article>
    {{-- ===== 文章头图（后台配置 image 时展示） ===== --}}
    @if ($post->image)
    <div class="mx-auto max-w-5xl px-6 pt-10">
        <div class="aspect-[21/9] overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-100">
            <img src="{{ $post->image }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
        </div>
    </div>
    @endif

    <div class="mx-auto max-w-3xl px-6 py-12">
        <nav class="flex items-center gap-2 text-sm text-zinc-500">
            <a href="{{ route('blog') }}" class="transition hover:text-zinc-900">{{ __('blog.back_to_blog') }}</a>
            @if ($postCategory)
            <span aria-hidden="true">/</span>
            <a href="{{ route('blog', ['category' => $postCategory->category_id]) }}" class="transition hover:text-zinc-900">{{ $postCategory->title }}</a>
            @endif
        </nav>

        <header class="mt-6">
            <h1 class="text-3xl font-bold leading-tight tracking-tight text-zinc-900 sm:text-4xl">{{ $post->title }}</h1>
            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                @if ($postCategory)
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">{{ $postCategory->title }}</span>
                @endif
                <time datetime="{{ $post->datetime->toIso8601String() }}">{{ $post->datetime->isoFormat('LL') }}</time>
                <span aria-hidden="true">·</span>
                <span>{{ $readMinutes }} {{ __('blog.min_read') }}</span>
            </div>
            @if ($post->description)
            <p class="mt-4 border-l-2 border-brand-200 pl-4 leading-relaxed text-zinc-500">{{ $post->description }}</p>
            @endif
        </header>

        {{-- 正文（prose 排版；后台富文本直出） --}}
        <div class="mt-8 prose prose-zinc-700 max-w-none prose-headings:tracking-tight prose-headings:text-zinc-900 prose-a:text-brand-600 prose-img:rounded-2xl prose-img:shadow-lg">
            {!! $post->content !!}
        </div>

        {{-- 相关文章（同分类优先，controller 取 3 篇） --}}
        @if ($related->isNotEmpty())
        <aside class="mt-16 border-t border-zinc-200 pt-10">
            <h2 class="text-lg font-bold text-zinc-900">{{ __('blog.related') }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-3">
                @foreach ($related as $rel)
                <a href="{{ route('blog.post', $rel->url) }}" class="group flex flex-col rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-brand-200 hover:shadow-lg hover:shadow-brand-600/5">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        @if ($rel->category)
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 font-medium text-brand-700">{{ $rel->category->title }}</span>
                        @endif
                        <time datetime="{{ $rel->datetime->toIso8601String() }}">{{ $rel->datetime->isoFormat('LL') }}</time>
                    </div>
                    <h3 class="mt-2.5 line-clamp-3 text-sm font-semibold leading-snug text-zinc-900 transition group-hover:text-brand-600">{{ $rel->title }}</h3>
                </a>
                @endforeach
            </div>
        </aside>
        @endif

        <div class="mt-12 text-center">
            <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                {{ __('blog.back_to_blog') }}
            </a>
        </div>
    </div>
</article>
@endsection

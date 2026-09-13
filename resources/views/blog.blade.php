@extends('layouts.public')
@section('main_class', 'w-full')
@section('title', __('blog.title'))
@section('meta_description', __('blog.subtitle'))
@section('canonical', route('blog'))
@section('content')
@php
    /* 阅读时长估算：按正文字数 400 字/分钟，忽略 HTML 标签 */
    $readMinutes = fn ($post) => max(1, (int) ceil(mb_strlen(trim(strip_tags($post->content))) / 400));
@endphp
<div>
    {{-- ===== Hero（与 /tools 页同款深色渐变 + 网格纹理 + 光晕） ===== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-zinc-950 via-zinc-900 to-brand-950 py-16 text-center sm:py-20">
        <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 1px 1px,rgba(255,255,255,0.15) 1px,transparent 0);background-size:32px 32px"></div>
        <div class="pointer-events-none absolute -top-32 left-1/2 h-72 w-[42rem] -translate-x-1/2 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-64 w-64 rounded-full bg-brand-600/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-3xl px-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500/10 px-3.5 py-1.5 text-sm font-medium text-brand-300 ring-1 ring-inset ring-brand-500/20">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v13.5A2.25 2.25 0 0 0 6 21.75h10.5M12 15v1.5m0-4.5v1.5"/></svg>
                {{ __('landing.nav_blog') }}
            </span>
            <h1 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('blog.title') }}</h1>
            <p class="mt-3 text-zinc-400">{{ __('blog.subtitle') }}</p>
        </div>
    </div>

    {{-- ===== 分类筛选 + 文章列表 ===== --}}
    <div class="mx-auto max-w-7xl px-6 py-12">
        @if ($categories->isNotEmpty())
        <nav class="flex flex-wrap items-center justify-center gap-2" aria-label="{{ __('blog.title') }}">
            <a href="{{ route('blog') }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium transition {{ $category ? 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' : 'border-brand-200 bg-brand-50 text-brand-700' }}">{{ __('blog.all') }}</a>
            @foreach ($categories as $cat)
            <a href="{{ route('blog', ['category' => $cat->category_id]) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-medium transition {{ (string) $category === (string) $cat->category_id ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-zinc-200 bg-white text-zinc-600 hover:border-zinc-300 hover:text-zinc-900' }}">{{ $cat->title }}</a>
            @endforeach
        </nav>
        @endif


        {{-- 特写大卡（仅未筛选时的最新一篇） --}}
        @if ($featured)
        @php $fCategory = $featured->category; @endphp
        <a href="{{ route('blog.post', $featured->url) }}" class="group mt-10 grid overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-xl hover:shadow-zinc-900/5 md:grid-cols-2">
            <div class="relative aspect-[16/9] overflow-hidden md:aspect-auto md:min-h-[20rem]">
                @if ($featured->image)
                <img src="{{ $featured->image }}" alt="{{ $featured->title }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                @else
                <div class="absolute inset-0 bg-gradient-to-br from-brand-100 via-zinc-50 to-zinc-200">
                    <span class="absolute inset-0 grid place-items-center text-7xl font-black tracking-tight text-brand-200/80 select-none">{{ mb_substr($featured->title, 0, 1) }}</span>
                </div>
                @endif
            </div>
            <div class="flex flex-col justify-center p-8 md:p-10">
                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                    @if ($fCategory)
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 font-medium text-brand-700">{{ $fCategory->title }}</span>
                    @endif
                    <time datetime="{{ $featured->datetime->toIso8601String() }}">{{ $featured->datetime->isoFormat('LL') }}</time>
                    <span aria-hidden="true">·</span>
                    <span>{{ $readMinutes($featured) }} {{ __('blog.min_read') }}</span>
                </div>
                <h2 class="mt-4 text-2xl font-bold leading-snug text-zinc-900 transition group-hover:text-brand-600 md:text-3xl">{{ $featured->title }}</h2>
                @if ($featured->description)
                <p class="mt-3 line-clamp-3 leading-relaxed text-zinc-500">{{ $featured->description }}</p>
                @endif
                <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
                    {{ __('blog.read_more') }}
                    <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </div>
        </a>
        @endif

        {{-- 文章网格 --}}
        @if ($posts->isNotEmpty())
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
            @php $pCategory = $post->category; @endphp
            <a href="{{ route('blog.post', $post->url) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white transition hover:border-zinc-300 hover:shadow-xl hover:shadow-zinc-900/5">
                @if ($post->image)
                <div class="aspect-[16/9] overflow-hidden">
                    <img src="{{ $post->image }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
                </div>
                @else
                <div class="relative aspect-[16/9] bg-gradient-to-br from-brand-50 via-zinc-50 to-zinc-100">
                    <span class="absolute inset-0 grid place-items-center text-4xl font-black text-brand-200/80 select-none">{{ mb_substr($post->title, 0, 1) }}</span>
                </div>
                @endif
                <div class="flex flex-1 flex-col p-6">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                        @if ($pCategory)
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 font-medium text-brand-700">{{ $pCategory->title }}</span>
                        @endif
                        <time datetime="{{ $post->datetime->toIso8601String() }}">{{ $post->datetime->isoFormat('LL') }}</time>
                    </div>
                    <h2 class="mt-3 text-lg font-semibold leading-snug text-zinc-900 transition group-hover:text-brand-600">{{ $post->title }}</h2>
                    @if ($post->description)
                    <p class="mt-2 line-clamp-2 text-sm text-zinc-500">{{ $post->description }}</p>
                    @endif
                    <span class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-medium text-brand-600">
                        {{ __('blog.read_more') }}
                        <svg class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                    </span>
                </div>
            </a>
            @endforeach
        </div>
        @elseif (! $featured)
        <div class="mt-16 text-center text-zinc-400">{{ __('common.no_articles') }}</div>
        @endif
    </div>
</div>
@endsection

@extends('layouts.public')
@section('main_class', 'w-full')
@section('title', $article->title)
@section('meta_description', $article->description ?: \Illuminate\Support\Str::limit(trim(strip_tags($article->content)), 157))
@section('content')
{{-- 帮助文章详情（A3）：面包屑 + 正文 + 相关文章 --}}
<div class="mx-auto max-w-7xl px-6 py-10">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-zinc-500" aria-label="Breadcrumb">
        <a href="{{ route('help') }}" class="transition hover:text-brand-700">{{ __('help.title') }}</a>
        <span class="text-zinc-300">/</span>
        @if ($article->category)
        <span class="text-zinc-500">{{ $article->category->title }}</span>
        <span class="text-zinc-300">/</span>
        @endif
        <span class="font-medium text-zinc-900">{{ $article->title }}</span>
    </nav>

    <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_300px]">
        <article class="min-w-0 rounded-3xl border border-zinc-200 bg-white p-8 lg:p-10">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">{{ $article->title }}</h1>
            <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400">
                @if ($article->category)
                <span class="rounded-full bg-brand-50 px-2.5 py-1 font-medium text-brand-700">{{ $article->category->title }}</span>
                @endif
                <span>{{ __('help.updated_at') }}: {{ $article->datetime->format('Y-m-d') }}</span>
                <span>{{ __('help.views') }}: {{ $article->views }}</span>
            </p>

            <div class="prose prose-zinc mt-8 max-w-none text-zinc-700">{!! $article->content !!}</div>
        </article>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-semibold text-zinc-900">{{ __('help.related_articles') }}</h2>
                <ul class="mt-3 space-y-2.5">
                    @forelse ($related as $item)
                    <li>
                        <a href="{{ route('help.article', $item->url) }}" class="flex items-start gap-2 text-sm text-zinc-600 transition hover:text-brand-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-6 3h3M12 3 4.5 6v6c0 4.5 3 8 7.5 9 4.5-1 7.5-4.5 7.5-9V6L12 3z"/></svg>
                            <span>{{ $item->title }}</span>
                        </a>
                    </li>
                    @empty
                    <li class="text-sm text-zinc-400">{{ __('common.no_articles') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 p-5 text-white">
                <h2 class="text-sm font-semibold">{{ __('help.contact_banner_title') }}</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-white/75">{{ __('help.contact_banner_desc') }}</p>
                <a href="{{ route('contact') }}" class="mt-3 inline-block rounded-xl bg-white px-4 py-2 text-xs font-medium text-brand-700 transition hover:bg-brand-50">{{ __('help.contact_banner_cta') }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection

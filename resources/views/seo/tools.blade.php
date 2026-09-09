@extends('layouts.public')
@section('title', __('seo.tools_title'))
@section('content')
@php
    /* 分类图标映射：分类标题与工具卡片共用（Heroicons outline 风格） */
    $catIcons = [
        'network'   => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>',
        'seo_check' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 8.25v6m3-3h-6"/></svg>',
        'preview'   => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        'minify'    => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/></svg>',
        'text'      => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
        'dev'       => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>',
    ];
    $catIcon = fn (string $category): string => $catIcons[$category] ?? ($catIcons['dev'] ?? '');
    $toolCount = $categories->sum(fn ($tools) => count($tools));
@endphp
<div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-zinc-950 via-zinc-900 to-brand-950 px-6 py-16 text-center sm:px-12 sm:py-20">
        <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 1px 1px,rgba(255,255,255,0.15) 1px,transparent 0);background-size:32px 32px"></div>
        <div class="pointer-events-none absolute -top-32 left-1/2 h-72 w-[42rem] -translate-x-1/2 rounded-full bg-brand-500/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-24 h-64 w-64 rounded-full bg-brand-600/20 blur-3xl"></div>
        <div class="relative">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3.5 py-1.5 text-sm font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ $toolCount }} {{ __('seo.landing_tests_available') }}
            </span>
            <h1 class="mt-6 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ __('seo.tools_title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-400">{{ __('seo.tools_subtitle') }}</p>
            <div class="mx-auto mt-8 max-w-xl">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" id="tools-search" placeholder="{{ __('seo.search_tools') }}"
                           class="w-full rounded-2xl border border-white/10 bg-white/5 py-3.5 pl-12 pr-4 text-sm text-white placeholder-zinc-500 outline-none transition focus:border-brand-500/50 focus:bg-white/10 focus:ring-2 focus:ring-brand-500/20">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 flex flex-wrap items-center justify-center gap-2" id="cat-nav">
        <button data-cat="all" class="cat-btn active rounded-xl px-4 py-2 text-sm font-medium transition">{{ __('seo.category_all') }}</button>
        @foreach($categories as $category => $tools)
            <button data-cat="{{ $category }}" class="cat-btn rounded-xl px-4 py-2 text-sm font-medium transition">{{ __("seo.category_{$category}") }}</button>
        @endforeach
    </div>

    @foreach($categories as $category => $tools)
        <div class="mt-10" data-category="{{ $category }}">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100">
                    {!! $catIcon($category) !!}
                </span>
                <h2 class="text-lg font-semibold text-zinc-900">{{ __("seo.category_{$category}") }}</h2>
                <span class="ml-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500">{{ $tools->count() }}</span>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($tools as $slug => $meta)
                    <a href="{{ route('seo.tools.show', $slug) }}"
                       class="tool-card group relative rounded-2xl border border-zinc-200 bg-white p-5 transition-all hover:border-brand-200 hover:shadow-lg hover:shadow-brand-600/5"
                       data-name="{{ __("seo.tool_name.{$slug}") }}"
                       data-desc="{{ __("seo.tool_desc.{$slug}") }}"
                       data-cat="{{ $category }}">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 transition group-hover:bg-brand-50 group-hover:text-brand-600">
                                {!! $catIcon($category) !!}
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-zinc-900 transition group-hover:text-brand-600">{{ __("seo.tool_name.{$slug}") }}</h3>
                                <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ __("seo.tool_desc.{$slug}") }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    <p id="no-results" class="mt-12 hidden text-center text-zinc-400">{{ __('seo.no_tools_found') }}</p>
</div>

<style>
.cat-btn { border: 1px solid #e4e4e7; background: #fff; color: #52525b; }
.cat-btn:hover { background: #fafafa; color: #18181b; }
.cat-btn.active { border-color: #c7d2fe; background: #eef2ff; color: #4338ca; }
</style>

<script>
(function() {
    var searchInput = document.getElementById('tools-search');
    var catNav = document.getElementById('cat-nav');
    var noResults = document.getElementById('no-results');
    var activeCat = 'all';

    catNav.addEventListener('click', function(e) {
        var btn = e.target.closest('.cat-btn');
        if (!btn) return;
        catNav.querySelectorAll('.cat-btn').forEach(function(b) { b.classList.remove('active'); });
        btn.classList.add('active');
        activeCat = btn.dataset.cat;
        applyFilters();
    });

    searchInput.addEventListener('input', function() { applyFilters(); });

    function applyFilters() {
        var q = searchInput.value.toLowerCase().trim();
        var anyVisible = false;
        document.querySelectorAll('[data-category]').forEach(function(catEl) {
            var catKey = catEl.dataset.category;
            var catMatch = activeCat === 'all' || catKey === activeCat;
            var visibleCount = 0;
            catEl.querySelectorAll('.tool-card').forEach(function(card) {
                var nameMatch = !q || card.dataset.name.toLowerCase().includes(q) || card.dataset.desc.toLowerCase().includes(q);
                var show = catMatch && nameMatch;
                card.classList.toggle('hidden', !show);
                if (show) visibleCount++;
            });
            if (catMatch && visibleCount > 0) { catEl.classList.remove('hidden'); anyVisible = true; }
            else { catEl.classList.add('hidden'); }
        });
        noResults.classList.toggle('hidden', anyVisible);
    }
})();
</script>
@endsection

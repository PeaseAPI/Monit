@extends('layouts.public')
@section('title', __('seo.tools_title'))
@section('content')
<div>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 px-6 py-16 text-center sm:px-12 sm:py-20">
        <div class="absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 1px 1px,rgba(255,255,255,0.15) 1px,transparent 0);background-size:32px 32px"></div>
        <div class="relative">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3.5 py-1.5 text-sm font-medium text-emerald-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ $categories->count() * 3 }}+ {{ __('seo.landing_tests_available') }}
            </span>
            <h1 class="mt-6 text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ __('seo.tools_title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-400">{{ __('seo.tools_subtitle') }}</p>
            <div class="mx-auto mt-8 max-w-xl">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    {!! [
                        'network' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>',
                        'seo_check' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        'preview' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
                        'minify' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>',
                        'text' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16"/></svg>',
                        'dev' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
                    ][$category] ?? '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>' !!}
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
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
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

@extends('layouts.public')
@section('main_class', 'w-full')
@section('title', $article->title)
@section('meta_description', $article->description ?: \Illuminate\Support\Str::limit(trim(strip_tags($article->content)), 157))
@section('content')
{{-- 帮助文档详情（对标 help.aliyun.com 文档页）：左目录树 + 中正文 + 右本页目录（TOC） --}}
<div class="mx-auto max-w-7xl px-6 py-10">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-zinc-500" aria-label="Breadcrumb">
        <a href="{{ route('help') }}" class="transition hover:text-brand-700">{{ __('help.title') }}</a>
        <span class="text-zinc-300">/</span>
        @if ($article->category)
        <a href="{{ route('help') }}#help-cat-{{ $article->category->category_id }}" class="text-zinc-500 transition hover:text-brand-700">{{ $article->category->title }}</a>
        <span class="text-zinc-300">/</span>
        @endif
        <span class="font-medium text-zinc-900">{{ $article->title }}</span>
    </nav>

    <div class="mt-6 xl:grid xl:grid-cols-[240px_minmax(0,1fr)_220px] xl:gap-8">
        {{-- 左：文档目录树（xl+ 显示；小屏折叠于顶部） --}}
        <aside class="mb-6 xl:mb-0">
            <details open class="rounded-2xl border border-zinc-200 bg-white xl:border-0 xl:bg-transparent xl:p-0">
                <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 font-semibold text-zinc-900 xl:hidden [&::-webkit-details-marker]:hidden">
                    {{ __('help.doc_nav') }}
                    <svg class="h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-4 xl:p-0">
                    <div class="xl:sticky xl:top-24 xl:max-h-[calc(100vh-8rem)] xl:overflow-y-auto xl:pr-1">
                        @include('help._nav', ['navCategories' => $navCategories, 'navArticles' => $navArticles, 'currentArticleUrl' => $article->url, 'collapsible' => false])
                    </div>
                </div>
            </details>
        </aside>

        {{-- 中：正文 --}}
        <article class="min-w-0 rounded-3xl border border-zinc-200 bg-white px-6 py-8 lg:px-10">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">{{ $article->title }}</h1>
            <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-400">
                @if ($article->category)
                <span class="rounded-full bg-brand-50 px-2.5 py-1 font-medium text-brand-700">{{ $article->category->title }}</span>
                @endif
                <span>{{ __('help.updated_at') }}: {{ $article->datetime->format('Y-m-d') }}</span>
                <span>{{ __('help.views') }}: {{ $article->views }}</span>
            </p>

            <div class="help-doc mt-8 text-[15px] leading-7 text-zinc-700">{!! $article->content !!}</div>

            {{-- 上一篇 / 下一篇 --}}
            @if ($prevArticle || $nextArticle)
            <div class="mt-10 grid gap-3 border-t border-zinc-100 pt-6 sm:grid-cols-2">
                @if ($prevArticle)
                <a href="{{ route('help.article', $prevArticle->url) }}" class="group rounded-xl border border-zinc-200 p-4 transition hover:border-brand-300 hover:shadow-sm">
                    <span class="text-xs text-zinc-400">&larr; {{ __('help.prev_article') }}</span>
                    <p class="mt-1 truncate text-sm font-medium text-zinc-700 group-hover:text-brand-700">{{ $prevArticle->title }}</p>
                </a>
                @endif
                @if ($nextArticle)
                <a href="{{ route('help.article', $nextArticle->url) }}" class="group rounded-xl border border-zinc-200 p-4 text-right transition hover:border-brand-300 hover:shadow-sm {{ $prevArticle ? '' : 'sm:col-start-2' }}">
                    <span class="text-xs text-zinc-400">{{ __('help.next_article') }} &rarr;</span>
                    <p class="mt-1 truncate text-sm font-medium text-zinc-700 group-hover:text-brand-700">{{ $nextArticle->title }}</p>
                </a>
                @endif
            </div>
            @endif

            {{-- 相关文章 --}}
            <div class="mt-8 border-t border-zinc-100 pt-6">
                <h2 class="text-sm font-semibold text-zinc-900">{{ __('help.related_articles') }}</h2>
                <ul class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                    @forelse ($related as $item)
                    <li>
                        <a href="{{ route('help.article', $item->url) }}" class="flex items-start gap-2 text-sm text-zinc-600 transition hover:text-brand-700">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-6 3h3M12 3 4.5 6v6c0 4.5 3 8 7.5 9 4.5-1 7.5-4.5 7.5-9V6L12 3z"/></svg>
                            <span class="min-w-0 truncate">{{ $item->title }}</span>
                        </a>
                    </li>
                    @empty
                    <li class="text-sm text-zinc-400">{{ __('common.no_articles') }}</li>
                    @endforelse
                </ul>
            </div>
        </article>
        {{-- 右：本页目录（TOC，由 JS 从正文 h2/h3 生成；xl+ 显示） --}}
        <aside class="hidden xl:block">
            <div class="sticky top-24">
                <div class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <p class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                        <svg class="h-4 w-4 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/></svg>
                        {{ __('help.on_this_page') }}
                    </p>
                    <nav id="help-toc" class="help-toc mt-3 max-h-[60vh] space-y-0.5 overflow-y-auto text-sm" aria-label="{{ __('help.on_this_page') }}"></nav>
                </div>
                <div class="mt-4 rounded-2xl bg-gradient-to-br from-brand-600 to-indigo-700 p-5 text-white">
                    <p class="text-sm font-semibold">{{ __('help.contact_banner_title') }}</p>
                    <p class="mt-1.5 text-xs leading-relaxed text-white/75">{{ __('help.contact_banner_desc') }}</p>
                    <a href="{{ route('contact') }}" class="mt-3 inline-block rounded-xl bg-white px-4 py-2 text-xs font-medium text-brand-700 transition hover:bg-brand-50">{{ __('help.contact_banner_cta') }}</a>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var doc = document.querySelector('.help-doc');
    var toc = document.getElementById('help-toc');
    if (!doc || !toc) return;

    // 从正文 h2/h3 生成锚点目录（无 id 的自动补 id）
    var headings = doc.querySelectorAll('h2, h3');
    var items = [];
    headings.forEach(function (h, i) {
        if (!h.id) h.id = 'doc-h-' + i;
        items.push({ tag: h.tagName.toLowerCase(), id: h.id, text: h.textContent.trim() });
    });

    if (!items.length) { toc.closest('.rounded-2xl').style.display = 'none'; return; }

    var links = {};
    items.forEach(function (item) {
        var a = document.createElement('a');
        a.href = '#' + item.id;
        a.textContent = item.text;
        a.className = 'toc-lv-' + item.tag + ' block truncate rounded-md border-l-2 border-transparent px-2 py-1 text-zinc-500 transition hover:text-brand-700';
        if (item.tag === 'h3') a.style.paddingLeft = '1.4rem';
        toc.appendChild(a);
        links[item.id] = a;
    });

    // 滚动高亮当前章节
    var active = null;
    function highlight() {
        var current = items[0] && items[0].id;
        for (var i = 0; i < items.length; i++) {
            var el = document.getElementById(items[i].id);
            if (el && el.getBoundingClientRect().top <= 120) current = items[i].id;
        }
        if (active === current) return;
        if (active && links[active]) links[active].classList.remove('toc-active');
        active = current;
        if (active && links[active]) {
            links[active].classList.add('toc-active');
        }
    }
    window.addEventListener('scroll', highlight, { passive: true });
    highlight();
})();
</script>
@endpush

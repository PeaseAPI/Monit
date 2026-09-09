{{-- 帮助中心主体（样式对标 help.aliyun.com）：渐变 hero + 大搜索框 + 分类区块 + 文章卡片 --}}
@php
    $articlesByCategory = $articles->groupBy(fn ($a) => $a->category_id ?? 0);
    $searchData = $articles->map(fn ($a) => [
        'title' => $a->title,
        'desc' => (string) $a->description,
        'url' => route('help.article', $a->url),
        'category' => $categories->firstWhere('category_id', $a->category_id)?->title ?? __('help.uncategorized'),
    ])->values();
@endphp
<div class="bg-gradient-to-br from-sky-700 via-blue-700 to-indigo-800 pb-20 pt-16 text-white">
    <div class="mx-auto max-w-4xl px-6 text-center">
        <h1 class="text-4xl font-bold tracking-tight">{{ __('help.title') }}</h1>
        <p class="mt-3 text-base text-white/70">{{ __('help.subtitle') }}</p>

        {{-- 全文搜索（前端实时过滤下方文章卡片） --}}
        <div class="relative mx-auto mt-8 max-w-2xl">
            <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0z"/></svg>
            <input type="search" data-help-search
                   placeholder="{{ __('help.search_placeholder') }}"
                   class="w-full rounded-2xl border-0 bg-white py-4 pr-5 text-sm text-zinc-900 shadow-xl shadow-blue-900/20 placeholder:text-zinc-400 focus:ring-2 focus:ring-sky-300" style="padding-left:3.25rem">
        </div>

        @if ($hasContent && $categories->isNotEmpty())
        <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
            @foreach ($categories as $category)
            <a href="#help-cat-{{ $category->category_id }}"
               class="rounded-full bg-white/10 px-4 py-1.5 text-sm text-white/90 backdrop-blur transition hover:bg-white/20">{{ $category->title }}</a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<div class="mx-auto max-w-7xl px-6 py-12">
    @if (! $hasContent)
        {{-- 回退：后台尚无帮助内容，展示内置静态条目 --}}
        <div class="mx-auto max-w-4xl space-y-6">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6"><h2 class="text-lg font-semibold">{{ __('help.install_tracking') }}</h2><p class="mt-2 text-sm text-zinc-600">{{ __('help.install_tracking_desc') }}</p></div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-6"><h2 class="text-lg font-semibold">{{ __('help.gdpr_compliant') }}</h2><p class="mt-2 text-sm text-zinc-600">{{ __('help.gdpr_compliant_desc') }}</p></div>
        </div>
    @else
        <p data-help-empty class="hidden py-16 text-center text-sm text-zinc-400">{{ __('help.no_results') }}</p>

        @foreach ($categories as $category)
            @php($catArticles = $articlesByCategory->get($category->category_id, collect()))
            @continue($catArticles->isEmpty())
        <section id="help-cat-{{ $category->category_id }}" data-help-region class="scroll-mt-24 {{ $loop->first ? '' : 'mt-12' }}">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
                <h2 class="text-xl font-bold text-zinc-900">{{ $category->title }}</h2>
                <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs text-zinc-500">{{ $catArticles->count() }}</span>
            </div>
            @include('help._cards', ['cards' => $catArticles])
        </section>
        @endforeach

        @php($orphanArticles = $articlesByCategory->get(0, collect()))
        @if ($orphanArticles->isNotEmpty())
        <section data-help-region class="mt-12">
            <h2 class="text-xl font-bold text-zinc-900">{{ __('help.uncategorized') }}</h2>
            @include('help._cards', ['cards' => $orphanArticles])
        </section>
        @endif
    @endif

    {{-- 没找到答案 → 联系客服横幅 --}}
    <div class="mt-14 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-gradient-to-r from-brand-600 to-indigo-700 px-8 py-7 text-white">
        <div>
            <h3 class="text-lg font-semibold">{{ __('help.contact_banner_title') }}</h3>
            <p class="mt-1 text-sm text-white/75">{{ __('help.contact_banner_desc') }}</p>
        </div>
        <a href="{{ route('contact') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-medium text-brand-700 transition hover:bg-brand-50">{{ __('help.contact_banner_cta') }}</a>
    </div>
</div>

<script type="application/json" data-help-data>{{ json_encode($searchData) }}</script>

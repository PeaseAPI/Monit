{{-- 帮助文档目录树（help/_nav）：左侧 sticky 导航，供 /help 与文章详情页复用。
    参数：$navCategories（分类集合） $navArticles（已发布文章集合）
          $currentArticleUrl（详情页当前文章 url，nullable） $collapsible（bool 首页移动端折叠模式） --}}
@php
    $articlesByCategory = $navArticles->groupBy(fn ($a) => $a->category_id ?? 0);
@endphp
<nav class="help-nav text-sm" aria-label="{{ __('help.doc_nav') }}">
    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('help.doc_nav') }}</p>
    <ul class="space-y-1">
        @foreach ($navCategories as $category)
            @php($catArticles = $articlesByCategory->get($category->category_id, collect()))
            @continue($catArticles->isEmpty())
        <li>
            <details class="group/cat" @if ($collapsible) open @elseif($currentArticleUrl && $catArticles->contains('url', $currentArticleUrl)) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 font-medium text-zinc-700 transition hover:bg-zinc-100 [&::-webkit-details-marker]:hidden">
                    <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 transition group-open/cat:rotate-90" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                    <span class="truncate">{{ $category->title }}</span>
                </summary>
                <ul class="ml-4 border-l border-zinc-200 pl-2">
                    @foreach ($catArticles as $item)
                    <li>
                        <a href="{{ route('help.article', $item->url) }}" data-help-nav-item
                           class="block truncate rounded-md px-2.5 py-1.5 text-zinc-500 transition hover:text-brand-700
                                  {{ $currentArticleUrl === $item->url ? 'bg-brand-50 font-medium text-brand-700' : '' }}"
                           @if ($collapsible) data-help-nav-title="{{ $item->title }}" @endif>{{ $item->title }}</a>
                    </li>
                    @endforeach
                </ul>
            </details>
        </li>
        @endforeach

        @php($orphanArticles = $articlesByCategory->get(0, collect()))
        @if ($orphanArticles->isNotEmpty())
        <li>
            <p class="flex items-center gap-2 rounded-lg px-3 py-2 font-medium text-zinc-700">{{ __('help.uncategorized') }}</p>
            <ul class="ml-4 border-l border-zinc-200 pl-2">
                @foreach ($orphanArticles as $item)
                <li><a href="{{ route('help.article', $item->url) }}" data-help-nav-item
                       class="block truncate rounded-md px-2.5 py-1.5 text-zinc-500 transition hover:text-brand-700"
                       @if ($collapsible) data-help-nav-title="{{ $item->title }}" @endif>{{ $item->title }}</a></li>
                @endforeach
            </ul>
        </li>
        @endif
    </ul>
</nav>

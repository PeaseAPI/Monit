{{-- 帮助文章行式列表（help/_cards：阿里云文档列表样式，供分类区块与未分类区复用） --}}
<ul class="mt-3 divide-y divide-zinc-100">
    @foreach ($cards as $article)
    <li>
        <a href="{{ route('help.article', $article->url) }}" data-help-card
           class="group -mx-3 flex items-start justify-between gap-4 rounded-lg px-3 py-3 transition hover:bg-zinc-50">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-medium text-zinc-800 transition group-hover:text-brand-700">{{ $article->title }}</h3>
                @if ($article->description)
                <p class="mt-1 line-clamp-1 text-xs leading-relaxed text-zinc-500">{{ $article->description }}</p>
                @endif
            </div>
            <span class="mt-0.5 flex shrink-0 items-center gap-1 text-xs text-zinc-400">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $article->views }}
            </span>
        </a>
    </li>
    @endforeach
</ul>

{{-- 帮助文章卡片网格（help/_cards：供分类区块与未分类区复用） --}}
<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($cards as $article)
    <a href="{{ route('help.article', $article->url) }}" data-help-card
       class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-md hover:shadow-brand-600/5">
        <h3 class="text-sm font-semibold text-zinc-900 group-hover:text-brand-700">{{ $article->title }}</h3>
        @if ($article->description)
        <p class="mt-2 line-clamp-2 text-xs leading-relaxed text-zinc-500">{{ $article->description }}</p>
        @endif
        <p class="mt-3 flex items-center gap-1 text-xs text-zinc-400">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ $article->views }}
        </p>
    </a>
    @endforeach
</div>

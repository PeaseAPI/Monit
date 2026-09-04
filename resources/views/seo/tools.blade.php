@extends('layouts.public')
@section('title', __('seo.tools_title'))
@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.tools_title') }}</h1>
        <input type="text" id="tools-search" placeholder="{{ __('seo.search_tools') }}" class="w-64 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
    </div>

    @foreach($categories as $category => $tools)
        <div class="mt-6" data-category="{{ $category }}">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ __("seo.category_{$category}") }}</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($tools as $slug => $meta)
                    <a href="{{ route('seo.tools.show', $slug) }}"
                       class="tool-card rounded-2xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-400"
                       data-name="{{ __("seo.tool_name.{$slug}") }}"
                       data-desc="{{ __("seo.tool_desc.{$slug}") }}">
                        <div class="font-medium text-zinc-900">{{ __("seo.tool_name.{$slug}") }}</div>
                        <div class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ __("seo.tool_desc.{$slug}") }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    <p id="no-results" class="mt-8 hidden text-center text-zinc-500">{{ __('seo.no_tools_found') }}</p>
</div>

<script>
document.getElementById('tools-search').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    var anyVisible = false;
    document.querySelectorAll('[data-category]').forEach(function(cat) {
        var visible = 0;
        cat.querySelectorAll('.tool-card').forEach(function(card) {
            var match = card.dataset.name.toLowerCase().includes(q) || card.dataset.desc.toLowerCase().includes(q);
            card.classList.toggle('hidden', !match);
            if (match) visible++;
        });
        cat.classList.toggle('hidden', visible === 0);
        if (visible > 0) anyVisible = true;
    });
    document.getElementById('no-results').classList.toggle('hidden', anyVisible);
});
</script>
@endsection

@props(['title', 'items', 'showRank' => false])
<div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
    <h3 class="text-sm font-semibold text-zinc-700">{{ $title }}</h3>
    @if (empty($items))
        <p class="mt-4 text-sm text-zinc-400">{{ __('stats.no_data') }}</p>
    @else
        @php $panelMax = max(1, max(array_column($items, 'count'))); @endphp
        <ul class="mt-4 space-y-3">
            @foreach ($items as $i => $item)
                <li>
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="flex items-center gap-2">
                            @if ($showRank)
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-500">{{ $i + 1 }}</span>
                            @endif
                            <span class="truncate font-medium text-zinc-700">{{ $item['label'] ?? (filled($item['key'] ?? null) ? $item['key'] : __('stats.unknown')) }}</span>
                        </span>
                        <span class="shrink-0 tabular-nums text-zinc-500">{{ number_format($item['count']) }}</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full bg-brand-500/70" style="width: {{ (int) round($item['count'] / $panelMax * 100) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

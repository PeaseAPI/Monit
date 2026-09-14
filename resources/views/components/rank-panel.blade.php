@props(['title', 'items', 'showRank' => false])
<div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
    <h3 class="text-sm font-semibold text-zinc-700">{{ $title }}</h3>
    @if (empty($items))
        <p class="mt-4 text-sm text-zinc-400">{{ __('stats.no_data') }}</p>
    @else
        @php $panelMax = max(1, max(array_column($items, 'count'))); @endphp
        <ul class="mt-4 space-y-3">
            @foreach ($items as $i => $item)
                <li class="group">
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <span class="flex min-w-0 items-center gap-2">
                            @if ($showRank)
                                @php $rank = $i + 1; @endphp
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $rank === 1 ? 'bg-amber-100 text-amber-700' : ($rank === 2 ? 'bg-zinc-200 text-zinc-600' : ($rank === 3 ? 'bg-orange-100 text-orange-700' : 'bg-zinc-100 text-zinc-400')) }}">{{ $rank }}</span>
                            @endif
                            <span class="truncate font-medium text-zinc-700 transition group-hover:text-brand-600">{{ $item['label'] ?? (filled($item['key'] ?? null) ? $item['key'] : __('stats.unknown')) }}</span>
                        </span>
                        <span class="shrink-0 tabular-nums text-zinc-500">{{ number_format($item['count']) }}<span class="ml-1.5 text-xs text-zinc-400">{{ (int) round($item['count'] / $panelMax * 100) }}%</span></span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-brand-400 transition-all group-hover:from-brand-600 group-hover:to-brand-500" style="width: {{ (int) round($item['count'] / $panelMax * 100) }}%"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

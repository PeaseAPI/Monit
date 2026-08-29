@props(['routeName', 'website', 'range' => 7])
<div class="flex flex-wrap items-center gap-2 mb-6">
    <div class="flex rounded-xl border border-zinc-200 bg-white p-1 shadow-sm">
        @foreach ([1 => __('stats.range_today'), 7 => __('stats.range_7d'), 30 => __('stats.range_30d'), 90 => __('stats.range_90d')] as $r => $label)
            <a href="{{ route($routeName, ['website' => $website->website_id, 'range' => $r]) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $range === $r ? 'bg-brand-600 text-white' : 'text-zinc-600 hover:bg-zinc-100' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

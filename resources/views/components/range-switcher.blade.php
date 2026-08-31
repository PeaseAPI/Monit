@props(['routeName', 'website' => null, 'range' => 7, 'params' => []])
<div class="flex flex-wrap items-center gap-2 mb-6">
    <div class="flex rounded-xl border border-zinc-200 bg-white p-1 shadow-sm">
        @foreach ([1 => __('stats.range_today'), 7 => __('stats.range_7d'), 30 => __('stats.range_30d'), 90 => __('stats.range_90d')] as $r => $label)
            @php
                /* 回归修复：website 为空（公开统计页）时不携带 website_id，
                 * 否则 route() 缺 pixel_key 参数导致整页 500 */
                $query = array_merge($params, $website
                    ? ['website' => $website->website_id, 'range' => $r]
                    : ['range' => $r]);
            @endphp
            <a href="{{ route($routeName, $query) }}"
               aria-current="{{ $range === $r ? 'true' : 'false' }}"
               class="rounded-lg px-3 py-1.5 text-sm font-medium transition
                      {{ $range === $r
                          ? 'bg-gradient-to-r from-brand-600 to-brand-700 text-white shadow-sm shadow-brand-600/30'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

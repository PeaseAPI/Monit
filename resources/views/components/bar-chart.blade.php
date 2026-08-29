@props(['series', 'metric' => 'pageviews'])
<div class="mt-4 flex h-48 items-end gap-1.5">
    @php $maxVal = max(1, max(array_column($series, $metric))); @endphp
    @foreach ($series as $day)
        <div class="group relative flex h-full flex-1 items-end">
            <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition group-hover:from-brand-700 group-hover:to-brand-500" style="height: {{ max(2, (int) round($day[$metric] / $maxVal * 100)) }}%"></div>
            <div class="pointer-events-none absolute -top-2 left-1/2 z-10 hidden -translate-x-1/2 -translate-y-full rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs whitespace-nowrap text-white group-hover:block">
                <p class="font-medium">{{ $day['date'] }}</p>
                <p class="text-zinc-300">{{ __('stats.pageviews') }} {{ $day['pageviews'] }} · {{ __('stats.visitors') }} {{ $day['visitors'] }}</p>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-2 flex justify-between text-xs text-zinc-400">
    <span>{{ $series[0]['date'] ?? '' }}</span>
    <span>{{ $series[count($series) - 1]['date'] ?? '' }}</span>
</div>

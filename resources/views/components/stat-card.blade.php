@props(['label', 'value', 'hint' => ''])
<div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-medium text-zinc-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-bold tabular-nums">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-zinc-400">{{ $hint }}</p>
    @endif
</div>

@props(['website', 'title', 'backRoute' => 'stats.index', 'backLabel' => null])
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route($backRoute, $website->website_id) }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ $backLabel ?? __('stats.back_to_overview') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $title }} - {{ $website->name }}</h1>
    </div>
    {{ $slot }}
</div>

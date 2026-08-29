@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('api_docs.title') }}</h1>
    <div class="mt-6 space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('api_docs.auth') }}</h2>
            <p class="mt-2 text-sm text-zinc-600">{{ __('api_docs.auth') }}: <code class="rounded bg-zinc-100 px-1 text-xs">Authorization: Bearer YOUR_API_KEY</code></p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('api_docs.list_websites') }}</h2>
            <p class="mt-2 text-sm text-zinc-600"><code class="rounded bg-zinc-100 px-1">GET /api/websites</code></p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('api_docs.get_stats') }}</h2>
            <p class="mt-2 text-sm text-zinc-600"><code class="rounded bg-zinc-100 px-1">GET /api/stats/{website_id}?period=7d</code></p>
        </div>
    </div>
</div>
@endsection
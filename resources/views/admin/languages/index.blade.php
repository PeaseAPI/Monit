@extends('layouts.admin')
@section('title', __('admin.languages'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.languages') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.languages_subtitle') }}</p>
</div>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach($languages as $code => $meta)
    @php($flag = ['zh_CN' => '🇨🇳', 'zh_TW' => '🇹🇼', 'en' => '🇬🇧', 'ru' => '🇷🇺', 'be' => '🇧🇾', 'ms' => '🇲🇾'][$code] ?? '🌐')
    <div class="group rounded-2xl border border-zinc-200/80 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lg hover:shadow-zinc-900/5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-50 text-xl ring-1 ring-zinc-100">{{ $flag }}</span>
                <div>
                    <p class="font-semibold text-zinc-900">{{ $meta['name'] ?? $code }}</p>
                    <p class="font-mono text-xs text-zinc-400">{{ $code }}</p>
                </div>
                @if($meta['is_default'])<span class="badge-soft bg-emerald-50 text-emerald-700">{{ __('admin.default') }}</span>@endif
            </div>
            <a href="{{ route('admin.languages.edit', $code) }}" class="btn btn-secondary px-3.5 py-2 text-xs group-hover:border-brand-300 group-hover:text-brand-700">{{ __('admin.edit') }}</a>
        </div>
        <p class="mt-4 text-sm text-zinc-500">{{ __('admin.language_strings_count', ['count' => $meta['count']]) }}</p>
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.language_updated_at', ['time' => date('Y-m-d H:i', $meta['mtime'])]) }}</p>
    </div>
    @endforeach
</div>
@endsection

@extends('layouts.admin')
@section('title', __('admin.languages'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.languages') }}</h1></div>
<div class="grid gap-4 md:grid-cols-2">
    @foreach($languages as $code => $meta)
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="rounded-xl bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700">{{ $code }}</span>
                @if($meta['is_default'])<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">{{ __('admin.default') }}</span>@endif
            </div>
            <a href="{{ route('admin.languages.edit', $code) }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.edit') }}</a>
        </div>
        <p class="mt-3 text-sm text-zinc-500">{{ __('admin.language_strings_count', ['count' => $meta['count']]) }}</p>
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.language_updated_at', ['time' => date('Y-m-d H:i', $meta['mtime'])]) }}</p>
    </div>
    @endforeach
</div>
@endsection

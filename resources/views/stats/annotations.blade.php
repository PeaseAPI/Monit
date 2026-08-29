@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.annotations_title')">
        <a href="{{ route('stats.annotations.create', $website->website_id) }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('stats.add_annotation') }}</a>
    </x-stats-header>
    <div class="space-y-3">
        @forelse($annotations ?? [] as $a)
        <div class="rounded-2xl border border-zinc-200 bg-white p-4"><p class="text-sm font-medium">{{ $a->name }}</p><p class="mt-1 text-xs text-zinc-500">{{ $a->date }}</p></div>
        @empty<p class="text-zinc-500">{{ __('stats.no_annotations') }}</p>@endforelse
    </div>
</div>
@endsection
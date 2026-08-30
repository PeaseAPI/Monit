@extends('layouts.admin')
@section('title', __('admin.languages').' - '.$code)
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.languages') }}：{{ $code }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.language_edit_hint') }}</p>
    </div>
    <a href="{{ route('admin.languages.index') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50">← {{ __('admin.languages') }}</a>
</div>
<form method="POST" action="{{ route('admin.languages.update', $code) }}">
    @csrf
    <div class="rounded-2xl border border-zinc-200 bg-white divide-y divide-zinc-100">
        @php($i = 0)
        @foreach($strings as $key => $value)
            @if($i++ < 200)
            <div class="grid gap-2 p-4 md:grid-cols-2 md:items-center">
                <div class="min-w-0">
                    <p class="truncate text-xs font-mono text-zinc-500" title="{{ $key }}">{{ $key }}</p>
                </div>
                <input type="text" name="values[{{ $key }}]" value="{{ $value }}" class="w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none">
            </div>
            @endif
        @endforeach
        @if(count($strings) > 200)
        <div class="p-4 text-center text-sm text-zinc-500">{{ __('admin.language_first_200', ['total' => count($strings)]) }}</div>
        @endif
    </div>
    <div class="mt-4 flex justify-end">
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
</form>
@endsection

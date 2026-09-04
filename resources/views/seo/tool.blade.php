@extends('layouts.public')
@section('title', __("seo.tool_name.{$slug}"))
@section('content')
<div class="mx-auto max-w-3xl">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-zinc-500">
        <a href="{{ route('seo.tools') }}" class="transition hover:text-zinc-900">{{ __('seo.tools_title') }}</a>
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
        <span class="text-zinc-900">{{ __("seo.tool_name.{$slug}") }}</span>
    </nav>

    {{-- Tool Header --}}
    <div class="mt-6 rounded-2xl bg-gradient-to-br from-zinc-900 to-zinc-800 px-6 py-8 text-white sm:px-8">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            </span>
            <div>
                <h1 class="text-2xl font-bold">{{ __("seo.tool_name.{$slug}") }}</h1>
                <p class="mt-1 text-zinc-400">{{ __("seo.tool_desc.{$slug}") }}</p>
            </div>
        </div>
    </div>

        <form method="POST" action="{{ route('seo.tools.process', $slug) }}" class="mt-6 space-y-4 rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8">
        @csrf
        @foreach(($meta['fields'] ?? []) as $field => $type)
            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __("seo.field.{$field}", [], app()->getLocale()) !== "seo.field.{$field}" ? __("seo.field.{$field}") : \Illuminate\Support\Str::headline($field) }}</span>
                @if($type === 'textarea')
                    <textarea name="input[{{ $field }}]" rows="6" class="mt-1.5 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">{{ old("input.{$field}") }}</textarea>
                @elseif($type === 'number')
                    <input type="number" name="input[{{ $field }}]" value="{{ old("input.{$field}") }}" class="mt-1.5 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                @elseif(str_starts_with($type, 'select:'))
                    @php $options = explode(',', substr($type, 7)); @endphp
                    <select name="input[{{ $field }}]" class="mt-1.5 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        @foreach($options as $opt)
                            <option value="{{ $opt }}">{{ __("seo.option.{$opt}", [], app()->getLocale()) !== "seo.option.{$opt}" ? __("seo.option.{$opt}") : ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="input[{{ $field }}]" value="{{ old("input.{$field}") }}" class="mt-1.5 w-full rounded-xl border border-zinc-200 px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                @endif
            </label>
        @endforeach
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500/20 focus:ring-offset-2">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ __('seo.run') }}
        </button>
    </form>
    @error('input')<div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

        {{-- Result --}}
    @if(session('result') && session('result_slug') === $slug)
        @php $result = session('result'); @endphp
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 sm:p-8">
            <div class="flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                </span>
                <h2 class="font-semibold text-zinc-900">{{ __('seo.result') }}</h2>
            </div>
            @if(!empty($result['data']))
                <dl class="mt-4 space-y-3 text-sm">
                    @foreach($result['data'] as $label => $value)
                        <div class="flex gap-4 rounded-xl bg-zinc-50 px-4 py-3">
                            <dt class="w-36 shrink-0 font-medium text-zinc-500">{{ $label }}</dt>
                            <dd class="break-all text-zinc-900">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
            @if(!empty($result['text']))
                <pre class="mt-4 max-h-96 overflow-auto rounded-xl bg-zinc-900 p-4 text-xs leading-5 text-zinc-100">{{ $result['text'] }}</pre>
            @endif
            @if(!empty($result['error']))<p class="mt-3 text-sm text-red-600">{{ $result['error'] }}</p>@endif
        </div>
    @endif
</div>
@endsection

@extends('layouts.public')
@section('title', __("seo.tool_name.{$slug}"))
@section('content')
<div>
    <a href="{{ route('seo.tools') }}" class="text-sm text-zinc-500 hover:underline">← {{ __('seo.tools_title') }}</a>
    <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __("seo.tool_name.{$slug}") }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __("seo.tool_desc.{$slug}") }}</p>

    <form method="POST" action="{{ route('seo.tools.process', $slug) }}" class="mt-6 space-y-3 rounded-2xl border border-zinc-200 bg-white p-6">
        @csrf
        @foreach(($meta['fields'] ?? []) as $field => $type)
            <label class="block">
                <span class="text-sm font-medium text-zinc-700">{{ __("seo.field.{$field}", [], app()->getLocale()) !== "seo.field.{$field}" ? __("seo.field.{$field}") : \Illuminate\Support\Str::headline($field) }}</span>
                @if($type === 'textarea')
                    <textarea name="input[{{ $field }}]" rows="6" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">{{ old("input.{$field}") }}</textarea>
                @elseif($type === 'number')
                    <input type="number" name="input[{{ $field }}]" value="{{ old("input.{$field}") }}" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                @elseif(str_starts_with($type, 'select:'))
                    @php $options = explode(',', substr($type, 7)); @endphp
                    <select name="input[{{ $field }}]" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                        @foreach($options as $opt)
                            <option value="{{ $opt }}">{{ __("seo.option.{$opt}", [], app()->getLocale()) !== "seo.option.{$opt}" ? __("seo.option.{$opt}") : ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="input[{{ $field }}]" value="{{ old("input.{$field}") }}" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                @endif
            </label>
        @endforeach
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.run') }}</button>
    </form>
    @error('input')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    @if(session('result') && session('result_slug') === $slug)
        @php $result = session('result'); @endphp
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="font-semibold text-zinc-800">{{ __('seo.result') }}</h2>
            @if(!empty($result['data']))
                <dl class="mt-3 space-y-2 text-sm">
                    @foreach($result['data'] as $label => $value)
                        <div class="flex gap-4"><dt class="w-32 shrink-0 text-zinc-500">{{ $label }}</dt><dd class="break-all text-zinc-900">{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @if(!empty($result['text']))
                <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-zinc-50 p-4 text-xs leading-5 text-zinc-800">{{ $result['text'] }}</pre>
            @endif
            @if(!empty($result['error']))<p class="mt-3 text-sm text-red-600">{{ $result['error'] }}</p>@endif
        </div>
    @endif
</div>
@endsection

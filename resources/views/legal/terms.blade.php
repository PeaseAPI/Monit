{{-- 服务条款（content.terms_html 后台可编辑；留空回退默认文案） --}}
@extends('layouts.public')

@section('title', __('legal.terms_title'))

@section('content')
<div class="mx-auto max-w-3xl px-6 py-14">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('legal.terms_title') }}</h1>
    <div class="prose prose-zinc mt-8 max-w-none">
        @if (trim($html) !== '')
            {!! $html !!}
        @else
            <p>{{ __('legal.terms_default_body') }}</p>
        @endif
    </div>
</div>
@endsection

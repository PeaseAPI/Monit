@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ $page->title }}</h1>
    <div class="mt-6 prose text-zinc-700">{!! $page->content !!}</div>
</div>
@endsection
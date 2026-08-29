@extends('layouts.admin')
@section('title', __('admin.domain_list'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.domain_list') }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white p-6"><p class="text-sm text-zinc-500">{{ __('common.no_data') }}</p></div>
@endsection
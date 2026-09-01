@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('websites.import_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('websites.import_desc') }}</p>
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('websites.import.store') }}" enctype="multipart/form-data">@csrf
        <div><label class="block text-sm font-medium text-zinc-700">{{ 'CSV File' }}</label><input type="file" name="file" accept=".csv" class="form-input"></div>
        <button class="mt-4 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('websites.import_btn') }}</button>
        </form>
    </div>
</div>
@endsection
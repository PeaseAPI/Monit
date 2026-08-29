@extends('layouts.app', ['nav' => 'websites'])

@section('title', __('websites.create_title'))

@section('content')
    <div class="mx-auto max-w-xl">
        <h2 class="text-2xl font-bold">{{ __('websites.create_title') }}</h2>
        <p class="mt-1 text-sm text-zinc-500">{{ __('websites.create_desc') }}</p>

        <form method="POST" action="{{ route('websites.store') }}" class="mt-6 space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf
            @include('websites._fields')

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                        {{ __('websites.create_btn') }}
                </button>
                <a href="{{ route('websites.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-700">{{ __('websites.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection

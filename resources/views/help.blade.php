@extends('layouts.guest')
@section('content')
<div class="mx-auto max-w-4xl px-6 py-12">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('help.title') }}</h1>
    <div class="mt-8 space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6"><h2 class="text-lg font-semibold">{{ __('help.install_tracking') }}</h2><p class="mt-2 text-sm text-zinc-600">{{ __('help.install_tracking_desc') }}</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6"><h2 class="text-lg font-semibold">{{ __('help.gdpr_compliant') }}</h2><p class="mt-2 text-sm text-zinc-600">{{ __('help.gdpr_compliant_desc') }}</p></div>
    </div>
</div>
@endsection
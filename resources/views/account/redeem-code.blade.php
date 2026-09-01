@extends('layouts.app')
@section('title', __('account.redeem_code'))
@section('content')
<div class="mx-auto max-w-xl py-12">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.redeem_code') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.redeem_code_desc') }}</p>

    <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <form method="POST" action="{{ route('account.redeem-code') }}">@csrf
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('account.code') }}</label>
                <input type="text" name="code" class="form-input font-mono" required placeholder="XXXX-XXXX-XXXX">
            </div>
            <button class="mt-4 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.redeem') }}</button>
        </form>
    </div>

    @if(session('success'))
    <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
</div>
@endsection
@extends('layouts.app')
@section('title', __('account.delete_account'))
@section('content')
<div class="mx-auto max-w-xl py-12">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.delete_account') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.delete_account_desc') }}</p>

    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-6">
        <h2 class="font-semibold text-red-800">{{ __('account.delete_warning_title') }}</h2>
        <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
            <li>{{ __('account.delete_warning_websites') }}</li>
            <li>{{ __('account.delete_warning_data') }}</li>
            <li>{{ __('account.delete_warning_irreversible') }}</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('account.delete') }}" class="mt-6">
        @csrf @method('DELETE')
        <div>
            <label class="block text-sm font-medium text-zinc-700">{{ __('account.confirm_password') }}</label>
            <input type="password" name="password" class="form-input" required>
        </div>
        <button type="submit" class="mt-4 rounded-xl bg-red-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-700" onclick="return confirm('{{ __('account.delete_confirm_msg') }}')">{{ __('account.delete_account_btn') }}</button>
    </form>
</div>
@endsection
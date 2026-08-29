@extends('layouts.app')
@section('content')
<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.title') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.profile_api_desc') }}</p>

    {{-- 个人资料 --}}
    <form method="POST" action="{{ route('account.update') }}" class="mt-6 max-w-xl">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.name_label') }}</label><input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.email_label') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"></div>
        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.update_profile') }}</button>
    </div></form>

    {{-- 修改密码 --}}
    <div class="mt-10 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.change_password') }}</h3>
        <form method="POST" action="{{ route('account.update-password') }}" class="mt-4 space-y-4">@csrf @method('PUT')
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.current_password') }}</label><input type="password" name="current_password" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="current-password"></div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.new_password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="new-password"></div>
            <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.confirm_new_password') }}</label><input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="new-password"></div>
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.change_password_btn') }}</button>
        </form>
    </div>

    {{-- API 密钥 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.api_key') }}</h3>
        <div class="mt-2 flex items-center gap-2">
            <code class="rounded-xl bg-zinc-100 px-3 py-2 text-xs text-zinc-600">{{ $user->api_key ?? __('account.not_set') }}</code>
            <a href="{{ route('account.regenerate_api_key') }}" onclick="event.preventDefault();document.getElementById('api-regen').submit();" class="text-sm text-brand-600 hover:underline">{{ __('account.regenerate') }}</a>
            @if($user->api_key)
            <a href="{{ route('account.revoke_api_key') }}" onclick="event.preventDefault();document.getElementById('api-revoke').submit();" class="text-sm text-red-600 hover:underline">{{ __('account.revoke') }}</a>
            @endif
        </div>
        <form id="api-regen" method="POST" action="{{ route('account.regenerate_api_key') }}">@csrf @method('PUT')</form>
        @if($user->api_key)
        <form id="api-revoke" method="POST" action="{{ route('account.revoke_api_key') }}">@csrf @method('DELETE')</form>
        @endif
    </div>

    {{-- 两步验证（规格书 §12.4） --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.twofa_title') }}</h3>
        <p class="mt-1 text-sm text-zinc-500">{{ __('account.twofa_desc') }}</p>

        @if($user->twofa_is_enabled)
            <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>{{ __('account.twofa_status_on') }}
            </p>
            <form method="POST" action="{{ route('account.twofa.disable') }}" class="mt-4 space-y-3" onsubmit="return confirm('{{ __('account.twofa_disable_confirm') }}')">@csrf @method('DELETE')
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.current_password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="current-password"></div>
                <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.twofa_code_label') }}</label><input type="text" name="code" inputmode="numeric" pattern="\d{6}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="one-time-code">@error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">{{ __('account.twofa_disable_btn') }}</button>
            </form>
        @else
            @if(session('twofa_setup'))
                <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                    <p class="text-xs text-zinc-500">{{ __('account.twofa_scan_hint') }}</p>
                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <img src="{{ session('twofa_setup')['qr'] }}" alt="TOTP QR" width="140" height="140" class="rounded-lg border border-zinc-200 bg-white p-1">
                        <div>
                            <p class="text-xs text-zinc-500">{{ __('account.twofa_manual_secret') }}</p>
                            <code class="mt-1 block break-all rounded-lg bg-white px-2 py-1.5 text-xs text-zinc-700 border border-zinc-200">{{ session('twofa_setup')['secret'] }}</code>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('account.twofa.enable') }}" class="mt-4 space-y-3">@csrf
                        <div><label class="block text-sm font-medium text-zinc-700">{{ __('account.twofa_code_label') }}</label><input type="text" name="code" inputmode="numeric" pattern="\d{6}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" autocomplete="one-time-code">@error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.twofa_confirm_enable') }}</button>
                    </form>
                </div>
            @else
                <a href="{{ route('account.twofa.setup') }}" class="mt-3 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.twofa_enable_btn') }}</a>
            @endif
        @endif
    </div>

    {{-- 删除账户 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-red-200 bg-red-50 p-6">
        <h3 class="text-sm font-semibold text-red-900">{{ __('account.delete_account') }}</h3>
        <p class="mt-1 text-sm text-red-700">{{ __('account.delete_warning') }}</p>
        <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 space-y-3" onsubmit="return confirm('{{ __('account.delete_confirm') }}')">@csrf @method('DELETE')
            <div><label class="block text-sm font-medium text-red-700">{{ __('account.current_password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-xl border border-red-300 px-4 py-2.5 text-sm" autocomplete="current-password"></div>
            <button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700">{{ __('account.delete_account_btn') }}</button>
        </form>
    </div>
</div>
@endsection
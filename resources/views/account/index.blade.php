@extends('layouts.app', ['nav' => 'account'])
@section('title', __('nav.account'))
@section('content')
<div class="max-w-2xl">
    {{-- 页头：头像 + 身份信息 --}}
    <div class="flex items-center gap-4">
        @if ($user->avatar)
            <img src="{{ $user->avatar }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover shadow-sm ring-2 ring-brand-100">
        @else
            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white shadow-sm">
                {{ mb_substr($user->name, 0, 1) }}
            </span>
        @endif
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold text-zinc-900">{{ $user->name }}</h1>
            <p class="truncate text-sm text-zinc-500">{{ $user->email }}</p>
        </div>
        <span class="ml-auto hidden rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 sm:inline-block">
            {{ $user->plan->name ?? __('payments.free_plan') }}
        </span>
    </div>

    {{-- 个人资料（含头像上传 / 防钓鱼码，对标 monit.cn /account） --}}
    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="card mt-6">@csrf @method('PUT')
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17 17 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z"/></svg>
            {{ __('account.profile_api_desc') }}
        </div>
        <div class="space-y-4 p-6">
            {{-- 头像 --}}
            @php($avatarMax = (int) (\App\Support\Settings::get('main.avatar_size_limit') ?: 512))
            <div class="flex flex-wrap items-center gap-4">
                <img id="avatar-preview" src="{{ $user->avatar }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl bg-zinc-100 object-cover ring-2 ring-zinc-200" @if(! $user->avatar) style="display:none" @endif>
                <span id="avatar-fallback" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white shadow-sm" @if($user->avatar) style="display:none" @endif>
                    {{ mb_substr($user->name, 0, 1) }}
                </span>
                <div class="min-w-0 flex-1">
                    <label class="form-label" for="acc-avatar">{{ __('account.avatar_label') }}</label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input id="acc-avatar" type="file" name="avatar" accept="image/*"
                               class="text-sm text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                               onchange="var f=this.files[0];if(f){var p=document.getElementById('avatar-preview');p.src=URL.createObjectURL(f);p.style.display='';document.getElementById('avatar-fallback').style.display='none'}">
                        @if ($user->avatar)
                            <label class="flex items-center gap-1.5 text-sm text-red-600">
                                <input type="checkbox" name="avatar_remove" value="1" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                                {{ __('account.avatar_remove') }}
                            </label>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-zinc-400">{{ __('account.avatar_hint', ['size' => $avatarMax]) }}</p>
                </div>
            </div>
            <div><label class="form-label" for="acc-name">{{ __('account.name_label') }}</label><input id="acc-name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input"></div>
            <div><label class="form-label" for="acc-email">{{ __('account.email_label') }}</label><input id="acc-email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input"></div>
            <div>
                <label class="form-label" for="acc-antiphishing">{{ __('account.anti_phishing_label') }}</label>
                <input id="acc-antiphishing" type="text" name="anti_phishing_code" maxlength="64" value="{{ old('anti_phishing_code', $user->anti_phishing_code) }}" class="form-input" placeholder="{{ __('account.anti_phishing_placeholder') }}">
                <p class="mt-1 text-xs text-zinc-400">{{ __('account.anti_phishing_hint') }}</p>
            </div>
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-2 focus:ring-brand-500/40 focus:outline-none">{{ __('account.update_profile') }}</button>
        </div>
    </form>

    {{-- 账单信息（对标 monit.cn /account billing；users.billing JSON 列） --}}
    @php($billing = old('billing', $user->billing ?? []))
    <form method="POST" action="{{ route('account.update') }}" class="card mt-6">@csrf @method('PUT')
        <input type="hidden" name="name" value="{{ $user->name }}">
        <input type="hidden" name="email" value="{{ $user->email }}">
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
            {{ __('account.billing_title') }}
        </div>
        <div class="space-y-4 p-6">
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="radio" name="billing_type" value="personal" class="text-brand-600 focus:ring-brand-500" {{ ($billing['type'] ?? 'personal') !== 'business' ? 'checked' : '' }}>
                    {{ __('account.billing_personal') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700">
                    <input type="radio" name="billing_type" value="business" class="text-brand-600 focus:ring-brand-500" {{ ($billing['type'] ?? '') === 'business' ? 'checked' : '' }}>
                    {{ __('account.billing_business') }}
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="form-label">{{ __('account.billing_name') }}</label><input type="text" name="billing_name" value="{{ old('billing_name', $billing['name'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_phone') }}</label><input type="text" name="billing_phone" value="{{ old('billing_phone', $billing['phone'] ?? '') }}" class="form-input"></div>
                <div class="sm:col-span-2"><label class="form-label">{{ __('account.billing_address') }}</label><input type="text" name="billing_address" value="{{ old('billing_address', $billing['address'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_city') }}</label><input type="text" name="billing_city" value="{{ old('billing_city', $billing['city'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_state') }}</label><input type="text" name="billing_state" value="{{ old('billing_state', $billing['state'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_county') }}</label><input type="text" name="billing_county" value="{{ old('billing_county', $billing['county'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_zip') }}</label><input type="text" name="billing_zip" value="{{ old('billing_zip', $billing['zip'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_country') }}</label><input type="text" name="billing_country" maxlength="2" placeholder="CN" value="{{ old('billing_country', $billing['country'] ?? '') }}" class="form-input"></div>
                <div><label class="form-label">{{ __('account.billing_tax_id') }}</label><input type="text" name="billing_tax_id" value="{{ old('billing_tax_id', $billing['tax_id'] ?? '') }}" class="form-input"></div>
                <div class="sm:col-span-2"><label class="form-label">{{ __('account.billing_notes') }}</label><textarea name="billing_notes" rows="2" class="form-input">{{ old('billing_notes', $billing['notes'] ?? '') }}</textarea></div>
            </div>
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('account.billing_save') }}</button>
        </div>
    </form>

    {{-- 修改密码 --}}
    <div class="card mt-6">
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            {{ __('account.change_password') }}
        </div>
        <form method="POST" action="{{ route('account.update-password') }}" class="space-y-4 p-6">@csrf @method('PUT')
            <div><label class="form-label">{{ __('account.current_password') }}</label><input type="password" name="current_password" class="form-input" autocomplete="current-password"></div>
            <div><label class="form-label">{{ __('account.new_password') }}</label><input type="password" name="password" class="form-input" autocomplete="new-password"></div>
            <div><label class="form-label">{{ __('account.confirm_new_password') }}</label><input type="password" name="password_confirmation" class="form-input" autocomplete="new-password"></div>
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('account.change_password_btn') }}</button>
        </form>
    </div>

    {{-- API 密钥 --}}
    <div class="card mt-6">
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18A2.25 2.25 0 0 1 20.25 10.5V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-7.5A2.25 2.25 0 0 1 10.5 8.25h6Z"/></svg>
            {{ __('account.api_key') }}
        </div>
        <div class="flex flex-wrap items-center gap-3 p-6">
            <code class="flex-1 truncate rounded-xl bg-zinc-100 px-3 py-2.5 text-xs text-zinc-600">{{ $user->api_key ?? __('account.not_set') }}</code>
            <a href="{{ route('account.regenerate_api_key') }}" onclick="event.preventDefault();document.getElementById('api-regen').submit();" class="rounded-xl border border-brand-600 px-4 py-2 text-sm font-medium text-brand-600 transition hover:bg-brand-50">{{ __('account.regenerate') }}</a>
            @if($user->api_key)
            <a href="{{ route('account.revoke_api_key') }}" onclick="event.preventDefault();document.getElementById('api-revoke').submit();" class="rounded-xl px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50">{{ __('account.revoke') }}</a>
            @endif
        </div>
        <form id="api-regen" method="POST" action="{{ route('account.regenerate_api_key') }}">@csrf @method('PUT')</form>
        @if($user->api_key)
        <form id="api-revoke" method="POST" action="{{ route('account.revoke_api_key') }}">@csrf @method('DELETE')</form>
        @endif
    </div>

    {{-- 手机号绑定（M17 §12.5） --}}
    @php($smsBindEnabled = \App\Services\Sms\SmsService::scenarioEnabled('phone_bind'))
    @if($smsBindEnabled)
    <div class="card mt-6">
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/></svg>
            {{ __('account.phone_title') }}
        </div>
        <div class="p-6">
        <p class="text-sm text-zinc-500">{{ __('account.phone_desc') }}</p>

        @if($user->phone)
            <div class="mt-3 flex items-center gap-2">
                <span class="rounded-xl bg-zinc-100 px-3 py-2 text-sm text-zinc-600">+86 {{ $user->phone }}</span>
                @if($user->phone_verified_at)
                    <span class="text-xs text-emerald-600">{{ __('account.phone_verified') }}</span>
                @endif
            </div>
            <p class="mt-2 text-xs text-zinc-400">{{ __('account.phone_rebind_hint') }}</p>
        @endif

        {{-- 第一步：向新手机号发送验证码 --}}
        <form method="POST" action="{{ route('sms.send') }}" class="mt-4 flex gap-2">
            @csrf
            <input type="hidden" name="purpose" value="phone_bind">
            <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="20" required
                   placeholder="{{ __('auth.phone_placeholder') }}"
                   class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm @error('phone') border-red-400 @enderror">
            <button class="whitespace-nowrap rounded-xl border border-brand-600 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-50">
                {{ __('auth.send_sms_code') }}
            </button>
            @error('phone')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </form>

        {{-- 第二步：提交验证码完成绑定 --}}
        <form method="POST" action="{{ route('account.phone.bind') }}" class="mt-3 space-y-3">
            @csrf
            <input type="hidden" name="phone" value="{{ old('phone', $user->phone) }}">
            <div>
                <label class="form-label">{{ __('auth.sms_code') }}</label>
                <input type="text" inputmode="numeric" maxlength="6" name="sms_code" required
                       placeholder="{{ __('auth.sms_code_placeholder') }}"
                       class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm @error('sms_code') border-red-400 @enderror">
                @error('sms_code')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <p class="text-xs text-zinc-400">{{ __('account.phone_bind_form_hint') }}</p>
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">{{ __('account.phone_bind_btn') }}</button>
        </form>
        </div>
    </div>
    @endif

    {{-- 两步验证（规格书 §12.4） --}}
    <div class="card mt-6">
        <div class="card-header flex items-center gap-2">
            <svg class="h-4 w-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M12 3l7.5 3v5.25c0 4.42-3.03 8.12-7.5 9.75-4.47-1.63-7.5-5.33-7.5-9.75V6L12 3Z"/></svg>
            {{ __('account.twofa_title') }}
        </div>
        <div class="p-6">
        <p class="text-sm text-zinc-500">{{ __('account.twofa_desc') }}</p>

        @if($user->twofa_is_enabled)
            <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>{{ __('account.twofa_status_on') }}
            </p>
            <form method="POST" action="{{ route('account.twofa.disable') }}" class="mt-4 space-y-3" onsubmit="return confirm('{{ __('account.twofa_disable_confirm') }}')">@csrf @method('DELETE')
                <div><label class="form-label">{{ __('account.current_password') }}</label><input type="password" name="password" class="form-input" autocomplete="current-password"></div>
                <div><label class="form-label">{{ __('account.twofa_code_label') }}</label><input type="text" name="code" inputmode="numeric" pattern="\d{6}" class="form-input" autocomplete="one-time-code">@error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
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
                        <div><label class="form-label">{{ __('account.twofa_code_label') }}</label><input type="text" name="code" inputmode="numeric" pattern="\d{6}" class="form-input" autocomplete="one-time-code">@error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.twofa_confirm_enable') }}</button>
                    </form>
                </div>
            @else
                <a href="{{ route('account.twofa.setup') }}" class="mt-3 inline-block rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('account.twofa_enable_btn') }}</a>
            @endif
        @endif
        </div>
    </div>

    {{-- 删除账户 --}}
    <div class="mt-6 rounded-2xl border border-red-200 bg-red-50/60">
        <div class="border-b border-red-100 bg-red-100/40 px-6 py-4 text-sm font-semibold text-red-900">{{ __('account.delete_account') }}</div>
        <div class="p-6">
        <p class="text-sm text-red-700/90">{{ __('account.delete_warning') }}</p>
        <form method="POST" action="{{ route('account.destroy') }}" class="mt-4 space-y-3" onsubmit="return confirm('{{ __('account.delete_confirm') }}')">@csrf @method('DELETE')
            <div><label class="form-label !text-red-700">{{ __('account.current_password') }}</label><input type="password" name="password" class="form-input border-red-300 focus:border-red-500" autocomplete="current-password"></div>
            <button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">{{ __('account.delete_account_btn') }}</button>
        </form>
        </div>
    </div>
</div>
@endsection
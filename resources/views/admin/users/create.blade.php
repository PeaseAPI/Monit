@extends('layouts.admin')
@section('title', __('admin.create_user'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.users.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_user') }}</h1></div>
<div class="max-w-2xl rounded-2xl border border-zinc-200 bg-white shadow-sm">
    <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">@csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">{{ __('admin.user_name') }}</label>
                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
            </div>
            <div>
                <label class="form-label">{{ __('contact.email_label') }}</label>
                <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="form-label">{{ __('admin.user_password') }}</label>
                <input type="password" name="password" class="form-input" required>
            </div>
            <div>
                <label class="form-label">{{ __('admin.col_plan') }}</label>
                <select name="plan_id" class="form-select">
                    <option value="free" {{ old('plan_id', 'free') === 'free' ? 'selected' : '' }}>Free</option>
                    @foreach ($plans ?? [] as $p)
                        <option value="{{ $p->plan_id }}" {{ old('plan_id') === (string) $p->plan_id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('admin.user_type') }}</label>
                <select name="type" class="form-select">
                    <option value="0" {{ old('type', '0') === '0' ? 'selected' : '' }}>{{ __('admin.type_user') }}</option>
                    <option value="1" {{ old('type') === '1' ? 'selected' : '' }}>{{ __('admin.type_admin') }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('admin.user_status') }}</label>
                <select name="status" class="form-select">
                    <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>{{ __('admin.status_active') }}</option>
                    <option value="0" {{ old('status') === '0' ? 'selected' : '' }}>{{ __('admin.status_unconfirmed') }}</option>
                    <option value="2" {{ old('status') === '2' ? 'selected' : '' }}>{{ __('admin.status_disabled') }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('admin.uv_language') }}</label>
                <select name="language" class="form-select">
                    @foreach (['zh_CN' => '简体中文', 'zh_TW' => '繁體中文', 'en' => 'English', 'ru' => 'Русский', 'be' => 'Беларуская', 'ms' => 'Melayu'] as $v => $l)
                        <option value="{{ $v }}" {{ old('language', 'zh_CN') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('admin.uv_timezone') }}</label>
                <input type="text" name="timezone" class="form-input" value="{{ old('timezone', 'Asia/Shanghai') }}">
            </div>
        </div>
        <div class="mt-6">
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
        </div>
    </form>
</div>
@endsection
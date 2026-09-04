<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.sms.t_fce244') }}</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="sms_is_enabled" value="1" {{ filter_var($settings['sms.sms_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        {{ __('settings.sms.enable_sms') }}
        </label>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">{{ __('settings.sms.t_f3550a') }}</label>
                <select name="sms_provider" class="form-select">
                    <option value="log" {{ ($settings['sms.sms_provider'] ?? 'log') === 'log' ? 'selected' : '' }}>{{ __('settings.sms.t_154601') }}</option>
                    <option value="aliyun" {{ ($settings['sms.sms_provider'] ?? '') === 'aliyun' ? 'selected' : '' }}>{{ __('settings.sms.t_6a7686') }}</option>
                    <option value="tencent" {{ ($settings['sms.sms_provider'] ?? '') === 'tencent' ? 'selected' : '' }}>{{ __('settings.sms.t_05b990') }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('settings.sms.t_540e1f') }}</label>
                <input type="number" name="sms_code_ttl_minutes" class="form-input" value="{{ old('sms_code_ttl_minutes', $settings['sms.sms_code_ttl_minutes'] ?? 10) }}">
            </div>
            <div>
                <label class="form-label">{{ __('settings.sms.t_7318c6') }}</label>
                <input type="number" name="sms_resend_interval_seconds" class="form-input" value="{{ old('sms_resend_interval_seconds', $settings['sms.sms_resend_interval_seconds'] ?? 60) }}">
            </div>
        </div>

        {{-- 阿里云短信 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">{{ __('settings.sms.t_90d443') }}</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">AccessKey ID</label>
                    <input type="text" name="sms_aliyun_access_key_id" class="form-input" value="{{ old('sms_aliyun_access_key_id', $settings['sms.sms_aliyun_access_key_id'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">AccessKey Secret</label>
                    <input type="password" name="sms_aliyun_access_key_secret" class="form-input" value="{{ old('sms_aliyun_access_key_secret', $settings['sms.sms_aliyun_access_key_secret'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.sms.t_635fb6') }}</label>
                    <input type="text" name="sms_aliyun_sign_name" class="form-input" value="{{ old('sms_aliyun_sign_name', $settings['sms.sms_aliyun_sign_name'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.sms.t_38c055') }}</label>
                    <input type="text" name="sms_aliyun_template_code" class="form-input" value="{{ old('sms_aliyun_template_code', $settings['sms.sms_aliyun_template_code'] ?? '') }}" placeholder="SMS_123456789">
                </div>
            </div>
        </fieldset>

        {{-- 腾讯云短信 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">{{ __('settings.sms.t_ac2cb3') }}</legend>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">SecretId</label>
                    <input type="text" name="sms_tencent_secret_id" class="form-input" value="{{ old('sms_tencent_secret_id', $settings['sms.sms_tencent_secret_id'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">SecretKey</label>
                    <input type="password" name="sms_tencent_secret_key" class="form-input" value="{{ old('sms_tencent_secret_key', $settings['sms.sms_tencent_secret_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">SdkAppId</label>
                    <input type="text" name="sms_tencent_sdk_app_id" class="form-input" value="{{ old('sms_tencent_sdk_app_id', $settings['sms.sms_tencent_sdk_app_id'] ?? '') }}" placeholder="1400000000">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.sms.t_c80efa') }}</label>
                    <input type="text" name="sms_tencent_sign_name" class="form-input" value="{{ old('sms_tencent_sign_name', $settings['sms.sms_tencent_sign_name'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.sms.t_5ce6e2') }}</label>
                    <input type="text" name="sms_tencent_template_id" class="form-input" value="{{ old('sms_tencent_template_id', $settings['sms.sms_tencent_template_id'] ?? '') }}">
                </div>
            </div>
        </fieldset>

        {{-- 场景开关 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-3">
            <legend class="px-1 text-sm font-medium text-zinc-700">{{ __('settings.sms.t_3dbf0c') }}</legend>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_register_is_enabled" value="1" {{ filter_var($settings['sms.sms_register_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                {{ __('settings.sms.signup_hint') }}
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_phone_login_is_enabled" value="1" {{ filter_var($settings['sms.sms_phone_login_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                {{ __('settings.sms.phone_login_hint') }}
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_forgot_password_is_enabled" value="1" {{ filter_var($settings['sms.sms_forgot_password_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                {{ __('settings.sms.reset_password_hint') }}
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_phone_bind_is_enabled" value="1" {{ filter_var($settings['sms.sms_phone_bind_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                {{ __('settings.sms.account_center_hint') }}
            </label>
            {{-- 登录二次校验（用户反馈 #16）：已绑手机号用户登录强制验证码 --}}
            <label class="flex items-center gap-2 border-t border-zinc-100 pt-3">
                <input type="checkbox" name="sms_login_verify_enabled" value="1" {{ filter_var($settings['sms.sms_login_verify_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                <span>{{ __('settings.sms.login_verify_hint') }}</span>
            </label>
        </fieldset>
    </div>
</div>

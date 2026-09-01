<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置短信验证服务（M17 规格书 §12.5：注册 / 登录 / 找回密码 / 绑定手机号，支持阿里云、腾讯云短信）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="sms_is_enabled" value="1" {{ filter_var($settings['sms.sms_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            启用短信功能
        </label>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="form-label">短信服务商</label>
                <select name="sms_provider" class="form-select">
                    <option value="log" {{ ($settings['sms.sms_provider'] ?? 'log') === 'log' ? 'selected' : '' }}>log（开发调试，仅写日志）</option>
                    <option value="aliyun" {{ ($settings['sms.sms_provider'] ?? '') === 'aliyun' ? 'selected' : '' }}>阿里云短信</option>
                    <option value="tencent" {{ ($settings['sms.sms_provider'] ?? '') === 'tencent' ? 'selected' : '' }}>腾讯云短信</option>
                </select>
            </div>
            <div>
                <label class="form-label">验证码有效期（分钟）</label>
                <input type="number" name="sms_code_ttl_minutes" class="form-input" value="{{ old('sms_code_ttl_minutes', $settings['sms.sms_code_ttl_minutes'] ?? 10) }}">
            </div>
            <div>
                <label class="form-label">重发间隔（秒）</label>
                <input type="number" name="sms_resend_interval_seconds" class="form-input" value="{{ old('sms_resend_interval_seconds', $settings['sms.sms_resend_interval_seconds'] ?? 60) }}">
            </div>
        </div>

        {{-- 阿里云短信 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">阿里云短信（dysmsapi）</legend>
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
                    <label class="form-label">签名名称（SignName）</label>
                    <input type="text" name="sms_aliyun_sign_name" class="form-input" value="{{ old('sms_aliyun_sign_name', $settings['sms.sms_aliyun_sign_name'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">模板 Code（TemplateCode，变量 ${code}）</label>
                    <input type="text" name="sms_aliyun_template_code" class="form-input" value="{{ old('sms_aliyun_template_code', $settings['sms.sms_aliyun_template_code'] ?? '') }}" placeholder="SMS_123456789">
                </div>
            </div>
        </fieldset>

        {{-- 腾讯云短信 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-4">
            <legend class="px-1 text-sm font-medium text-zinc-700">腾讯云短信（TC3）</legend>
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
                    <label class="form-label">签名内容（SignName）</label>
                    <input type="text" name="sms_tencent_sign_name" class="form-input" value="{{ old('sms_tencent_sign_name', $settings['sms.sms_tencent_sign_name'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">模板 ID（TemplateId，变量 {1}）</label>
                    <input type="text" name="sms_tencent_template_id" class="form-input" value="{{ old('sms_tencent_template_id', $settings['sms.sms_tencent_template_id'] ?? '') }}">
                </div>
            </div>
        </fieldset>

        {{-- 场景开关 --}}
        <fieldset class="rounded-xl border border-zinc-200 p-4 space-y-3">
            <legend class="px-1 text-sm font-medium text-zinc-700">应用场景</legend>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_register_is_enabled" value="1" {{ filter_var($settings['sms.sms_register_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                注册：要求手机号 + 短信验证码
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_phone_login_is_enabled" value="1" {{ filter_var($settings['sms.sms_phone_login_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                手机号登录：手机号 + 密码 / 手机号 + 短信验证码
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_forgot_password_is_enabled" value="1" {{ filter_var($settings['sms.sms_forgot_password_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                找回密码：支持手机号短信验证码重置
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="sms_phone_bind_is_enabled" value="1" {{ filter_var($settings['sms.sms_phone_bind_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                账户中心：允许绑定/更换手机号
            </label>
        </fieldset>
    </div>
</div>

<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置验证码保护设置（规格书 §6.1：注册受验证码保护）</p>

    <div class="grid grid-cols-1 gap-4">
        <div>
            <label class="form-label">验证码类型</label>
            <select name="captcha_type" class="form-select">
                <option value="none" {{ ($settings['captcha.captcha_type'] ?? 'none') === 'none' ? 'selected' : '' }}>禁用</option>
                <option value="recaptcha" {{ ($settings['captcha.captcha_type'] ?? '') === 'recaptcha' ? 'selected' : '' }}>Google reCAPTCHA v2</option>
                <option value="recaptcha_v3" {{ ($settings['captcha.captcha_type'] ?? '') === 'recaptcha_v3' ? 'selected' : '' }}>Google reCAPTCHA v3</option>
                <option value="hcaptcha" {{ ($settings['captcha.captcha_type'] ?? '') === 'hcaptcha' ? 'selected' : '' }}>hCaptcha</option>
                <option value="turnstile" {{ ($settings['captcha.captcha_type'] ?? '') === 'turnstile' ? 'selected' : '' }}>Cloudflare Turnstile</option>
            </select>
        </div>
        <div>
            <label class="form-label">Site Key</label>
            <input type="text" name="captcha_site_key" class="form-input" value="{{ old('captcha_site_key', $settings['captcha.captcha_site_key'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">Secret Key</label>
            <input type="password" name="captcha_secret_key" class="form-input" value="{{ old('captcha_secret_key', $settings['captcha.captcha_secret_key'] ?? '') }}">
        </div>

        <div class="mt-2">
            <h4 class="font-medium mb-2">验证码保护页面</h4>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_register" value="1" {{ ($settings['captcha.captcha_on_register'] ?? false) ? 'checked' : '' }}>
                    注册页面
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_login" value="1" {{ ($settings['captcha.captcha_on_login'] ?? false) ? 'checked' : '' }}>
                    登录页面
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_lost_password" value="1" {{ ($settings['captcha.captcha_on_lost_password'] ?? false) ? 'checked' : '' }}>
                    找回密码页面
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_contact" value="1" {{ ($settings['captcha.captcha_on_contact'] ?? false) ? 'checked' : '' }}>
                    联系表单
                </label>
            </div>
        </div>
    </div>
</div>


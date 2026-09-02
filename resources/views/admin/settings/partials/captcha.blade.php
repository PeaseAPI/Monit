<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.captcha.t_2e0c72') }}</p>

    <div class="grid grid-cols-1 gap-4">
        <div>
            <label class="form-label">{{ __('settings.captcha.t_1d2a02') }}</label>
            <select name="captcha_type" class="form-select">
                <option value="none" {{ ($settings['captcha.captcha_type'] ?? 'none') === 'none' ? 'selected' : '' }}>{{ __('settings.captcha.t_710ad0') }}</option>
                <option value="recaptcha" {{ ($settings['captcha.captcha_type'] ?? '') === 'recaptcha' ? 'selected' : '' }}>Google reCAPTCHA v2</option>
                <option value="recaptcha_v3" {{ ($settings['captcha.captcha_type'] ?? '') === 'recaptcha_v3' ? 'selected' : '' }}>Google reCAPTCHA v3</option>
                <option value="hcaptcha" {{ ($settings['captcha.captcha_type'] ?? '') === 'hcaptcha' ? 'selected' : '' }}>hCaptcha</option>
                <option value="turnstile" {{ ($settings['captcha.captcha_type'] ?? '') === 'turnstile' ? 'selected' : '' }}>Cloudflare Turnstile</option>
                <option value="geetest" {{ ($settings['captcha.captcha_type'] ?? '') === 'geetest' ? 'selected' : '' }}>{{ __('settings.captcha.geetest') }}</option>
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
            <h4 class="font-medium mb-1">{{ __('settings.captcha.t_290fc7') }}</h4>
            <p class="form-hint mb-2">{{ __('settings.captcha.t_adf9dc') }}</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="form-label">reCAPTCHA Site Key</label>
                    <input type="text" name="recaptcha_site_key" class="form-input" value="{{ old('recaptcha_site_key', $settings['captcha.recaptcha_site_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">reCAPTCHA Secret Key</label>
                    <input type="password" name="recaptcha_secret_key" class="form-input" value="{{ old('recaptcha_secret_key', $settings['captcha.recaptcha_secret_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">hCaptcha Site Key</label>
                    <input type="text" name="hcaptcha_site_key" class="form-input" value="{{ old('hcaptcha_site_key', $settings['captcha.hcaptcha_site_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">hCaptcha Secret Key</label>
                    <input type="password" name="hcaptcha_secret_key" class="form-input" value="{{ old('hcaptcha_secret_key', $settings['captcha.hcaptcha_secret_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Turnstile Site Key</label>
                    <input type="text" name="turnstile_site_key" class="form-input" value="{{ old('turnstile_site_key', $settings['captcha.turnstile_site_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">Turnstile Secret Key</label>
                    <input type="password" name="turnstile_secret_key" class="form-input" value="{{ old('turnstile_secret_key', $settings['captcha.turnstile_secret_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.captcha.geetest_id') }}</label>
                    <input type="text" name="geetest_site_key" class="form-input" value="{{ old('geetest_site_key', $settings['captcha.geetest_site_key'] ?? '') }}">
                </div>
                <div>
                    <label class="form-label">{{ __('settings.captcha.geetest_key') }}</label>
                    <input type="password" name="geetest_secret_key" class="form-input" value="{{ old('geetest_secret_key', $settings['captcha.geetest_secret_key'] ?? '') }}">
                </div>
            </div>
        </div>

        <div class="mt-2">
            <h4 class="font-medium mb-2">{{ __('settings.captcha.t_85018b') }}</h4>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_register" value="1" {{ filter_var($settings['captcha.captcha_on_register'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                        {{ __('settings.captcha.page_register') }}
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_login" value="1" {{ filter_var($settings['captcha.captcha_on_login'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                    {{ __('settings.captcha.page_login') }}
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_lost_password" value="1" {{ filter_var($settings['captcha.captcha_on_lost_password'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                    {{ __('settings.captcha.page_lost_password') }}
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="captcha_on_contact" value="1" {{ filter_var($settings['captcha.captcha_on_contact'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                    {{ __('settings.captcha.page_contact') }}
                </label>
            </div>
        </div>
    </div>
</div>


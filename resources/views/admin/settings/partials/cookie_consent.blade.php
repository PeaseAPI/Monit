<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置 Cookie 同意管理设置（规格书 §6.1：GDPR 同意记录）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="cookie_consent_is_enabled" value="1" {{ ($settings['cookie_consent.cookie_consent_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用 Cookie 同意横幅
        </label>
        <div>
            <label class="form-label">同意横幅标题</label>
            <input type="text" name="cookie_consent_title" class="form-input w-full" value="{{ old('cookie_consent_title', $settings['cookie_consent.cookie_consent_title'] ?? '我们使用 Cookie') }}">
        </div>
        <div>
            <label class="form-label">同意横幅描述</label>
            <textarea name="cookie_consent_description" class="form-input w-full" rows="3">{{ old('cookie_consent_description', $settings['cookie_consent.cookie_consent_description'] ?? '本网站使用 Cookie 来提升您的浏览体验并提供个性化服务。') }}</textarea>
        </div>
        <div>
            <label class="form-label">同意按钮文本</label>
            <input type="text" name="cookie_consent_button_text" class="form-input" value="{{ old('cookie_consent_button_text', $settings['cookie_consent.cookie_consent_button_text'] ?? '接受') }}">
        </div>
    </div>
</div>


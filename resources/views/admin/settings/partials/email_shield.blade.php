<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">邮箱防护</h3>
                <p class="settings-section-desc">混淆页面邮箱防爬取（原版 email_shield）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用邮箱防护</span>
                    <span class="settings-field-row-hint">自动混淆页面中的邮箱地址</span>
                </span>
                <input type="checkbox" name="email_shield_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['email_shield.email_shield_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">EmailShield API Key</label>
                <input type="text" name="email_shield_api_key" class="form-input" value="{{ old('email_shield_api_key', $settings['email_shield.email_shield_api_key'] ?? '') }}">
                <p class="form-hint">防护服务密钥</p>
            </div>
            <div>
                <label class="form-label">白名单域名</label>
                <textarea name="email_shield_whitelisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('email_shield_whitelisted_domains', $settings['email_shield.email_shield_whitelisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">这些域名上的邮箱不混淆，每行一个</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">防护统计</span>
                    <span class="settings-field-row-hint">记录拦截的爬虫请求</span>
                </span>
                <input type="checkbox" name="email_shield_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['email_shield.email_shield_statistics_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

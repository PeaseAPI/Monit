<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.email_shield.title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.email_shield.desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_shield.enable') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_shield.enable_hint') }}</span>
                </span>
                <input type="checkbox" name="email_shield_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['email_shield.email_shield_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">EmailShield API Key</label>
                <input type="text" name="email_shield_api_key" class="form-input" value="{{ old('email_shield_api_key', $settings['email_shield.email_shield_api_key'] ?? '') }}">
                <p class="form-hint">{{ __('settings.email_shield.api_key_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.email_shield.whitelisted_domains') }}</label>
                <textarea name="email_shield_whitelisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('email_shield_whitelisted_domains', $settings['email_shield.email_shield_whitelisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.email_shield.whitelisted_domains_hint') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_shield.statistics') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_shield.statistics_hint') }}</span>
                </span>
                <input type="checkbox" name="email_shield_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['email_shield.email_shield_statistics_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

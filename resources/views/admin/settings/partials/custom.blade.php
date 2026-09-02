<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.custom.code_injection_title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.custom.code_injection_desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">HEAD JS</label>
                <textarea name="custom_head_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_head_js', $settings['custom.custom_head_js'] ?? '') }}</textarea>
                                <p class="form-hint">{{ __('settings.custom.head_js_hint') }}</p>
            </div>
            <div>
                <label class="form-label">HEAD CSS</label>
                <textarea name="custom_head_css" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_head_css', $settings['custom.custom_head_css'] ?? '') }}</textarea>
                                <p class="form-hint">{{ __('settings.custom.head_css_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.custom.footer_js') }}</label>
                <textarea name="custom_footer_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_footer_js', $settings['custom.custom_footer_js'] ?? '') }}</textarea>
                                <p class="form-hint">{{ __('settings.custom.footer_js_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.custom.welcome_js') }}</label>
                <textarea name="custom_welcome_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_welcome_js', $settings['custom.custom_welcome_js'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.custom.welcome_js_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.custom.pay_thank_you_js') }}</label>
                <textarea name="custom_pay_thank_you_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_pay_thank_you_js', $settings['custom.custom_pay_thank_you_js'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.custom.pay_thank_you_js_hint') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.custom.body_content_title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.custom.body_content_desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                                <label class="form-label">{{ __('settings.custom.body_content') }}</label>
                <textarea name="custom_body_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_body_content', $settings['custom.custom_body_content'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.custom.body_content_hint') }}</p>
            </div>
        </div>
    </section>
</div>

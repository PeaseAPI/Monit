<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_b96130') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_e77a08') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_4df241') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_6d6976') }}</span>
                </span>
                <input type="checkbox" name="registration_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.registration_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_ce66fc') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_f3e0d0') }}</span>
                </span>
                <input type="checkbox" name="api_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.api_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_aa3cfd') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_4209da') }}</span>
                </span>
                <input type="checkbox" name="whitelabel_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.whitelabel_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_7ca9f0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_deb897') }}</span>
                </span>
                <input type="checkbox" name="force_https" value="1" class="input-toggle"
                    {{ filter_var($settings['main.force_https'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_d9c651') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_3e1961') }}</span>
                </span>
                <input type="checkbox" name="seo_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.seo_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_6ad6f1') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_3363f6') }}</span>
                </span>
                <input type="checkbox" name="iframe_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.iframe_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_1514c8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_318f99') }}</span>
                </span>
                <input type="checkbox" name="ai_crawlers_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.ai_crawlers_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_850ec1') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_67392f') }}</span>
                </span>
                <input type="checkbox" name="auto_language_detection_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.auto_language_detection_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_169bce') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_73023f') }}</span>
                </span>
                <input type="checkbox" name="breadcrumbs_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.breadcrumbs_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_c6354f') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_fc541f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_e94b4c') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_899b9f') }}</span>
                </span>
                <input type="checkbox" name="display_index_plans" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_plans'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_f83cb4') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_54fc96') }}</span>
                </span>
                <input type="checkbox" name="display_index_faq" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_faq'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_5028d8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_5b7e13') }}</span>
                </span>
                <input type="checkbox" name="display_index_latest_blog_posts" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_latest_blog_posts'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_972c4c') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_8c9e75') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_6bca1f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_b31dfd') }}</span>
                </span>
                <input type="checkbox" name="maintenance_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.maintenance_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.main.t_d2a86c') }}</label>
                <input type="text" name="maintenance_title" class="form-input" value="{{ old('maintenance_title', $settings['main.maintenance_title'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_b34e22') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_aa48d5') }}</label>
                <textarea name="maintenance_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('maintenance_description', $settings['main.maintenance_description'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.main.t_099f36') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_24bdc7') }}</label>
                <input type="text" name="maintenance_button_text" class="form-input" value="{{ old('maintenance_button_text', $settings['main.maintenance_button_text'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_44c868') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_73b976') }}</label>
                <input type="url" name="maintenance_button_url" class="form-input" value="{{ old('maintenance_button_url', $settings['main.maintenance_button_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_1ea3ea') }}</p>
            </div>
        </div>
    </section>
</div>

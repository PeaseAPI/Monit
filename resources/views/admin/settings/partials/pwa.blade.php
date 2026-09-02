<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.pwa.t_7b5761') }}</h3>
                <p class="settings-section-desc">{{ __('settings.pwa.t_b8a7a2') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.pwa.t_3a5876') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.pwa.t_58db9c') }}</span>
                </span>
                <input type="checkbox" name="pwa_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_27c386') }}</label>
                <input type="text" name="pwa_name" class="form-input" value="{{ old('pwa_name', $settings['pwa.pwa_name'] ?? '') }}">
                <p class="form-hint">{{ __('settings.pwa.t_7ee523') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_4a0e63') }}</label>
                <input type="text" name="pwa_short_name" class="form-input" value="{{ old('pwa_short_name', $settings['pwa.pwa_short_name'] ?? '') }}">
                <p class="form-hint">{{ __('settings.pwa.t_c29355') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_3fb775') }}</label>
                <textarea name="pwa_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('pwa_description', $settings['pwa.pwa_description'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.pwa.t_30e124') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_84a367') }}</label>
                <input type="url" name="pwa_app_start_url" class="form-input" value="{{ old('pwa_app_start_url', $settings['pwa.pwa_app_start_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.pwa.t_dc4813') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.pwa.t_afcde2') }}</h3>
                <p class="settings-section-desc">{{ __('settings.pwa.t_a0d02c') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.pwa.t_b47707') }}</label>
                <input type="text" name="pwa_theme_color" class="form-input" value="{{ old('pwa_theme_color', $settings['pwa.pwa_theme_color'] ?? '#6366f1') }}" placeholder="#6366f1">
                <p class="form-hint">{{ __('settings.pwa.t_6bdabf') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_2f97db') }}</label>
                <input type="text" name="pwa_background_color" class="form-input" value="{{ old('pwa_background_color', $settings['pwa.pwa_background_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">{{ __('settings.pwa.t_c91b48') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.pwa.t_ba2556') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.pwa.t_e7c4e1') }}</span>
                </span>
                <input type="checkbox" name="pwa_is_fullscreen" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_is_fullscreen'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.pwa.t_e60b06') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.pwa.t_b7f84f') }}</span>
                </span>
                <input type="checkbox" name="pwa_dynamic_splash_screen" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_dynamic_splash_screen'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.pwa.t_b2470e') }}</h3>
                <p class="settings-section-desc">{{ __('settings.pwa.t_d14d83') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.pwa.t_19b49c') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.pwa.t_771a69') }}</span>
                </span>
                <input type="checkbox" name="pwa_display_install_bar" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_display_install_bar'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.pwa.t_623e34') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.pwa.t_2cb28e') }}</span>
                </span>
                <input type="checkbox" name="pwa_display_install_bar_for_guests" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_display_install_bar_for_guests'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_573c25') }}</label>
                <input type="number" name="pwa_display_install_bar_delay" class="form-input" value="{{ old('pwa_display_install_bar_delay', $settings['pwa.pwa_display_install_bar_delay'] ?? '5000') }}" placeholder="5000">
                <p class="form-hint">{{ __('settings.pwa.t_a5fb1b') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.pwa.t_3f2e1d') }}</label>
                <input type="number" name="pwa_display_install_bar_minimum_pageviews_count" class="form-input" value="{{ old('pwa_display_install_bar_minimum_pageviews_count', $settings['pwa.pwa_display_install_bar_minimum_pageviews_count'] ?? '3') }}" placeholder="3">
                <p class="form-hint">{{ __('settings.pwa.t_8a6432') }}</p>
            </div>
        </div>
    </section>
</div>

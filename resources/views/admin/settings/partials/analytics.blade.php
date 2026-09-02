<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.analytics.t_08058d') }}</h3>
                <p class="settings-section-desc">{{ __('settings.analytics.t_7e9c0e') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_873d1d') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_267103') }}</span>
                </span>
                <input type="checkbox" name="ip_storage_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.ip_storage_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.analytics.t_e5ab2e') }}</label>
                <input type="number" name="sessions_replays_minimum_duration" class="form-input" value="{{ old('sessions_replays_minimum_duration', $settings['analytics.sessions_replays_minimum_duration'] ?? '0') }}" placeholder="0">
                <p class="form-hint">{{ __('settings.analytics.t_ac8dcc') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_e2a43e') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_3d5ef2') }}</span>
                </span>
                <input type="checkbox" name="pixel_cache" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.pixel_cache'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_3e2914') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_254e4f') }}</span>
                </span>
                <input type="checkbox" name="pixel_exposed_identifier" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.pixel_exposed_identifier'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_28cc22') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_11ce8b') }}</span>
                </span>
                <input type="checkbox" name="email_notices_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.email_notices_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.analytics.t_190980') }}</h3>
                <p class="settings-section-desc">{{ __('settings.analytics.t_7c9d28') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_c71a20') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_6224fd') }}</span>
                </span>
                <input type="checkbox" name="domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_ba03a9') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_8535b7') }}</span>
                </span>
                <input type="checkbox" name="additional_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.additional_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_417183') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_04b52b') }}</span>
                </span>
                <input type="checkbox" name="main_domain_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.main_domain_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.analytics.t_abdba9') }}</label>
                <input type="text" name="domains_custom_main_ip" class="form-input" value="{{ old('domains_custom_main_ip', $settings['analytics.domains_custom_main_ip'] ?? '') }}">
                <p class="form-hint">{{ __('settings.analytics.t_d8fd36') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.analytics.t_06cc1d') }}</h3>
                <p class="settings-section-desc">{{ __('settings.analytics.t_4001f1') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.analytics.t_8e140f') }}</label>
                <textarea name="blacklisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_domains', $settings['analytics.blacklisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.analytics.t_071d0d') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.analytics.t_71b009') }}</h3>
                <p class="settings-section-desc">{{ __('settings.analytics.t_a9ea7f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.analytics.t_1be5c5') }}</label>
                <input type="url" name="example_url" class="form-input" value="{{ old('example_url', $settings['analytics.example_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.analytics.t_51c1c9') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.analytics.t_899578') }}</h3>
                <p class="settings-section-desc">{{ __('settings.analytics.t_6ea22e') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_821e7a') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_381847') }}</span>
                </span>
                <input type="checkbox" name="email_reports_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.email_reports_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_30d47b') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_b6d4bf') }}</span>
                </span>
                <input type="checkbox" name="annotations_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.annotations_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_a5529e') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_b3f48f') }}</span>
                </span>
                <input type="checkbox" name="sessions_replays_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.sessions_replays_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_328ed3') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_633088') }}</span>
                </span>
                <input type="checkbox" name="websites_heatmaps_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.websites_heatmaps_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_a739eb') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_5c5fcc') }}</span>
                </span>
                <input type="checkbox" name="dashboard_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.dashboard_views_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_0c4e36') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_265a98') }}</span>
                </span>
                <input type="checkbox" name="custom_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.custom_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_552065') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_5043d0') }}</span>
                </span>
                <input type="checkbox" name="extra_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.extra_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.analytics.t_3c586b') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.analytics.t_51adfd') }}</span>
                </span>
                <input type="checkbox" name="outbound_clicks_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.outbound_clicks_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

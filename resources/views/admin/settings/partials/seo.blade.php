<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.seo.t_b96130') }}</h3>
                <p class="settings-section-desc">{{ __('settings.seo.t_92fef1') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_5e98bd') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_70a4ad') }}</span>
                </span>
                <input type="checkbox" name="audits_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.audits_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_ed23a7') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_97e34c') }}</span>
                </span>
                <input type="checkbox" name="tools_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.tools_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_835b47') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_a964f7') }}</span>
                </span>
                <input type="checkbox" name="tools_guest_access" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.tools_guest_access'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.seo.t_ca2634') }}</label>
                <input type="number" min="0" name="tools_guest_monthly_limit" class="form-input" value="{{ old('tools_guest_monthly_limit', $settings['seo.tools_guest_monthly_limit'] ?? 20) }}">
                <p class="form-hint">{{ __('settings.seo.t_34c3d4') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.seo.t_85e98d') }}</label>
                <textarea name="seo_disabled_tools" rows="3" class="form-input w-full font-mono text-[13px]">{{ old('seo_disabled_tools', $settings['seo.seo_disabled_tools'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.seo.t_0e744e') }}</p>
            </div>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.seo.t_c5e182') }}</h3>
                <p class="settings-section-desc">{{ __('settings.seo.t_73fe53') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.seo.t_9f9d22') }}</label>
                <input type="number" min="5" max="120" name="seo_request_timeout" class="form-input" value="{{ old('seo_request_timeout', $settings['seo.seo_request_timeout'] ?? 20) }}">
                <p class="form-hint">{{ __('settings.seo.t_7e7994') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.seo.t_6c495c') }}</label>
                <input type="text" name="seo_request_user_agent" class="form-input" value="{{ old('seo_request_user_agent', $settings['seo.seo_request_user_agent'] ?? 'Mozilla/5.0 (compatible; MonitBot/1.0)') }}">
                <p class="form-hint">{{ __('settings.seo.t_4bb689') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_72e451') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_fbfa7a') }}</span>
                </span>
                <input type="checkbox" name="seo_double_check" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.seo_double_check'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.seo.t_13e168') }}</label>
                <input type="number" min="1" max="10" name="seo_double_check_wait" class="form-input" value="{{ old('seo_double_check_wait', $settings['seo.seo_double_check_wait'] ?? 2) }}">
                <p class="form-hint">{{ __('settings.seo.t_5ad3e4') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.seo.t_3fa9aa') }}</label>
                <input type="text" name="serpapi_api_key" class="form-input font-mono text-[13px]" value="{{ old('serpapi_api_key', $settings['seo.serpapi_api_key'] ?? '') }}" autocomplete="off">
                <p class="form-hint">{{ __('settings.seo.t_b79c80') }}</p>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.seo.t_dd4045') }}</h3>
                <p class="settings-section-desc">{{ __('settings.seo.t_740ec6') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.seo.t_34d672') }}</label>
                <input type="text" name="domain_monitor_alert_days" class="form-input" value="{{ old('domain_monitor_alert_days', $settings['seo.domain_monitor_alert_days'] ?? '30,7,1') }}">
                <p class="form-hint">{{ __('settings.seo.t_fb148a') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.seo.t_06af94') }}</label>
                <input type="number" min="0" max="3650" name="archives_retention_days" class="form-input" value="{{ old('archives_retention_days', $settings['seo.archives_retention_days'] ?? 30) }}">
                <p class="form-hint">{{ __('settings.seo.t_acfc4d') }}</p>
            </div>
        </div>
    </section>
</div>

            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_b3deff') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_5f7629') }}</span>
                </span>
                <input type="checkbox" name="sitemap_monitor_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.sitemap_monitor_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.seo.t_26331c') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.seo.t_6ab078') }}</span>
                </span>
                <input type="checkbox" name="domain_monitor_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.domain_monitor_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>

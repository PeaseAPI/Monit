<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.cron.title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.cron.desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.cron.key') }}</label>
                <input type="text" name="cron_key" class="form-input" value="{{ old('cron_key', $settings['cron.cron_key'] ?? '') }}">
                <p class="form-hint">{{ __('settings.cron.key_hint') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cron.email_reports') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cron.email_reports_hint') }}</span>
                </span>
                <input type="checkbox" name="cron_email_reports" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_email_reports'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cron.broadcasts') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cron.broadcasts_hint') }}</span>
                </span>
                <input type="checkbox" name="cron_broadcasts" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_broadcasts'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cron.push_notifications') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cron.push_notifications_hint') }}</span>
                </span>
                <input type="checkbox" name="cron_push_notifications" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_push_notifications'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.internal_notifications.t_fc4ce4') }}</h3>
                <p class="settings-section-desc">{{ __('settings.internal_notifications.t_8a7940') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_b93460') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_b5db5c') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_876059') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_de9b8b') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_users_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_users_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_cf79a0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_a50af4') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_admins_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_admins_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.internal_notifications.t_add6b1') }}</h3>
                <p class="settings-section-desc">{{ __('settings.internal_notifications.t_977139') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_237043') }}</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_user" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_user'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_247e27') }}</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_delete_user" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_delete_user'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_4e7806') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_749043') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_new_newsletter_subscriber" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_newsletter_subscriber'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_4c8528') }}</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_payment" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_payment'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_48f481') }}</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_affiliate_withdrawal" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_affiliate_withdrawal'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_eb5dc9') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_923e5b') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_payment_success" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_payment_success'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_28930f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_f971aa') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_plan_expiry" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_plan_expiry'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.internal_notifications.t_9c1a64') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.internal_notifications.t_eb27df') }}</span>
                </span>
                <input type="checkbox" name="internal_notifications_limit_reached" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_limit_reached'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

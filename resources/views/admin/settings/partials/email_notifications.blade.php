<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.email_notifications.t_23beab') }}</h3>
                <p class="settings-section-desc">{{ __('settings.email_notifications.t_c220aa') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_237043') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_307de9') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_new_user" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_user'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_4c8528') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_bd172c') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_new_payment" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_payment'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_bb601a') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_df8dad') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_new_website" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_website'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_247e27') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_39e14d') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_delete_user" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_delete_user'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_f164fa') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_a5e1ef') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_new_domain" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_domain'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_0cbf83') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_1183c2') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_contact" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_contact'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_48f481') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_05ef87') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_new_affiliate_withdrawal" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_affiliate_withdrawal'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.email_notifications.t_867e06') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.email_notifications.t_4228cb') }}</span>
                </span>
                <input type="checkbox" name="email_notifications_user_plan_expiry_reminder" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_user_plan_expiry_reminder'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

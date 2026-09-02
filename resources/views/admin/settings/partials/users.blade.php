<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_289882') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_44a2a3') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_4df241') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_74e506') }}</span>
                </span>
                <input type="checkbox" name="register_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_0a1e72') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_7418fb') }}</span>
                </span>
                <input type="checkbox" name="email_activation_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.email_activation_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_b79f59') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_786846') }}</span>
                </span>
                <input type="checkbox" name="welcome_email_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.welcome_email_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_a94630') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_8f5c46') }}</span>
                </span>
                <input type="checkbox" name="user_registration_require_consent" value="1" class="input-toggle"
                    {{ filter_var($settings['users.user_registration_require_consent'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_8d6dfb') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_45877b') }}</span>
                </span>
                <input type="checkbox" name="register_display_newsletter_checkbox" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_display_newsletter_checkbox'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_0e2bd4') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_b156cd') }}</span>
                </span>
                <input type="checkbox" name="account_display_newsletter_checkbox" value="1" class="input-toggle"
                    {{ filter_var($settings['users.account_display_newsletter_checkbox'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_4404d5') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_bc6c36') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_dbad1e') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_df4090') }}</span>
                </span>
                <input type="checkbox" name="two_fa_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.two_fa_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_6b6ce0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_208028') }}</span>
                </span>
                <input type="checkbox" name="login_rememberme_checkbox_is_checked" value="1" class="input-toggle"
                    {{ filter_var($settings['users.login_rememberme_checkbox_is_checked'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_19329d') }}</label>
                <input type="number" name="login_rememberme_cookie_days" class="form-input" value="{{ old('login_rememberme_cookie_days', $settings['users.login_rememberme_cookie_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.users.t_2cacf7') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_bd6af7') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_eadb6f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_435434') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_804628') }}</span>
                </span>
                <input type="checkbox" name="auto_delete_unconfirmed_users" value="1" class="input-toggle"
                    {{ filter_var($settings['users.auto_delete_unconfirmed_users'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_df0373') }}</label>
                <input type="number" name="auto_delete_unconfirmed_users_days" class="form-input" value="{{ old('auto_delete_unconfirmed_users_days', $settings['users.auto_delete_unconfirmed_users_days'] ?? '3') }}" placeholder="3">
                <p class="form-hint">{{ __('settings.users.t_1aa1f9') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_a55cc6') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_d1e534') }}</span>
                </span>
                <input type="checkbox" name="auto_delete_inactive_users" value="1" class="input-toggle"
                    {{ filter_var($settings['users.auto_delete_inactive_users'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_799601') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_085935') }}</span>
                </span>
                <input type="checkbox" name="user_deletion_reminder" value="1" class="input-toggle"
                    {{ filter_var($settings['users.user_deletion_reminder'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_2e7a1f') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_227648') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.users.t_beaa65') }}</label>
                <textarea name="blacklisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_domains', $settings['users.blacklisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.users.t_f73fa5') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_0ca516') }}</label>
                <textarea name="blacklisted_ips" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_ips', $settings['users.blacklisted_ips'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.users.t_e539cc') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_1c3730') }}</label>
                <textarea name="blacklisted_countries" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_countries', $settings['users.blacklisted_countries'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.users.t_a90e34') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_64dd56') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_b4be03') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_be22c6') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_6b13c5') }}</span>
                </span>
                <input type="checkbox" name="login_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.login_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_07b3ef') }}</label>
                <input type="number" name="login_lockout_max_retries" class="form-input" value="{{ old('login_lockout_max_retries', $settings['users.login_lockout_max_retries'] ?? '5') }}" placeholder="5">
                <p class="form-hint">{{ __('settings.users.t_d28e3d') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_11d5e5') }}</label>
                <input type="number" name="login_lockout_time" class="form-input" value="{{ old('login_lockout_time', $settings['users.login_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.users.t_94fe70') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_69cabb') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_a8e169') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_923722') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_f92056') }}</span>
                </span>
                <input type="checkbox" name="lost_password_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.lost_password_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_5aee3e') }}</label>
                <input type="number" name="lost_password_lockout_max_retries" class="form-input" value="{{ old('lost_password_lockout_max_retries', $settings['users.lost_password_lockout_max_retries'] ?? '3') }}" placeholder="3">
                <p class="form-hint">{{ __('settings.users.t_d28e3d') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_11d5e5') }}</label>
                <input type="number" name="lost_password_lockout_time" class="form-input" value="{{ old('lost_password_lockout_time', $settings['users.lost_password_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.users.t_207c74') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_38c1f3') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_f9e340') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_b28988') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_f3eee4') }}</span>
                </span>
                <input type="checkbox" name="resend_activation_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.resend_activation_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_2d6392') }}</label>
                <input type="number" name="resend_activation_lockout_max_retries" class="form-input" value="{{ old('resend_activation_lockout_max_retries', $settings['users.resend_activation_lockout_max_retries'] ?? '3') }}" placeholder="3">
                <p class="form-hint">{{ __('settings.users.t_d28e3d') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_11d5e5') }}</label>
                <input type="number" name="resend_activation_lockout_time" class="form-input" value="{{ old('resend_activation_lockout_time', $settings['users.resend_activation_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.users.t_207c74') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_d90d83') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_b03876') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_f5111a') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_5ae846') }}</span>
                </span>
                <input type="checkbox" name="register_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_lockout_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.users.t_84cf46') }}</label>
                <input type="number" name="register_lockout_max_registrations" class="form-input" value="{{ old('register_lockout_max_registrations', $settings['users.register_lockout_max_registrations'] ?? '5') }}" placeholder="5">
                <p class="form-hint">{{ __('settings.users.t_9199e5') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.users.t_bb6288') }}</label>
                <input type="number" name="register_lockout_time" class="form-input" value="{{ old('register_lockout_time', $settings['users.register_lockout_time'] ?? '60') }}" placeholder="60">
                <p class="form-hint">{{ __('settings.users.t_a62cdd') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.users.t_556f67') }}</h3>
                <p class="settings-section-desc">{{ __('settings.users.t_1edffd') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.users.t_6da09c') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.users.t_0d2691') }}</span>
                </span>
                <input type="checkbox" name="api_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.api_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

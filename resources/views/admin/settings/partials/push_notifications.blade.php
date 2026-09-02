<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.push_notifications.t_b2878c') }}</h3>
                <p class="settings-section-desc">{{ __('settings.push_notifications.t_4b4f1c') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.push_notifications.t_a31cf7') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.push_notifications.t_2403a2') }}</span>
                </span>
                <input type="checkbox" name="push_notifications_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.push_notifications_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.push_notifications.t_b828b2') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.push_notifications.t_0e4c53') }}</span>
                </span>
                <input type="checkbox" name="push_notifications_guests_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.push_notifications_guests_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_622388') }}</label>
                <input type="text" name="push_notifications_public_key" class="form-input" value="{{ old('push_notifications_public_key', $settings['push_notifications.push_notifications_public_key'] ?? '') }}">
                <p class="form-hint">Web Push VAPID Public Key</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_b2c3ab') }}</label>
                <input type="password" name="push_notifications_private_key" autocomplete="new-password" class="form-input" value="{{ old('push_notifications_private_key', $settings['push_notifications.push_notifications_private_key'] ?? '') }}">
                <p class="form-hint">Web Push VAPID Private Key</p>
            </div>
            <div>
                <label class="form-label">VAPID Subject</label>
                <input type="text" name="push_notifications_vapid_subject" class="form-input" value="{{ old('push_notifications_vapid_subject', $settings['push_notifications.push_notifications_vapid_subject'] ?? '') }}">
                <p class="form-hint">{{ __('settings.push_notifications.t_5bfe81') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.push_notifications.t_3fc8f4') }}</h3>
                <p class="settings-section-desc">{{ __('settings.push_notifications.t_d0763f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.push_notifications.t_4ca821') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.push_notifications.t_f6fea6') }}</span>
                </span>
                <input type="checkbox" name="ask_to_subscribe_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.ask_to_subscribe_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_573c25') }}</label>
                <input type="number" name="ask_to_subscribe_delay" class="form-input" value="{{ old('ask_to_subscribe_delay', $settings['push_notifications.ask_to_subscribe_delay'] ?? '5000') }}" placeholder="5000">
                <p class="form-hint">{{ __('settings.push_notifications.t_293108') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_3f2e1d') }}</label>
                <input type="number" name="ask_to_subscribe_delay_minimum_pageviews_count" class="form-input" value="{{ old('ask_to_subscribe_delay_minimum_pageviews_count', $settings['push_notifications.ask_to_subscribe_delay_minimum_pageviews_count'] ?? '3') }}" placeholder="3">
                <p class="form-hint">{{ __('settings.push_notifications.t_320432') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.push_notifications.t_4376c7') }}</h3>
                <p class="settings-section-desc">{{ __('settings.push_notifications.t_420b32') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_16df0b') }}</label>
                <input type="number" name="push_notifications_subscribers_limit" class="form-input" value="{{ old('push_notifications_subscribers_limit', $settings['push_notifications.push_notifications_subscribers_limit'] ?? '1000') }}" placeholder="1000">
                <p class="form-hint">{{ __('settings.push_notifications.t_f77052') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_11ef77') }}</label>
                <input type="number" name="push_notifications_campaigns_limit" class="form-input" value="{{ old('push_notifications_campaigns_limit', $settings['push_notifications.push_notifications_campaigns_limit'] ?? '10') }}" placeholder="10">
                <p class="form-hint">{{ __('settings.push_notifications.t_d545ee') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_bb211c') }}</label>
                <input type="number" name="notifications_per_cron" class="form-input" value="{{ old('notifications_per_cron', $settings['push_notifications.notifications_per_cron'] ?? '100') }}" placeholder="100">
                <p class="form-hint">{{ __('settings.push_notifications.t_a332a8') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_0cc6d9') }}</label>
                <input type="number" name="notifications_per_cron_batch" class="form-input" value="{{ old('notifications_per_cron_batch', $settings['push_notifications.notifications_per_cron_batch'] ?? '50') }}" placeholder="50">
                <p class="form-hint">{{ __('settings.push_notifications.t_461937') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.push_notifications.t_3dc4b2') }}</label>
                <input type="number" name="notifications_per_cron_batch_concurrently" class="form-input" value="{{ old('notifications_per_cron_batch_concurrently', $settings['push_notifications.notifications_per_cron_batch_concurrently'] ?? '5') }}" placeholder="5">
                <p class="form-hint">{{ __('settings.push_notifications.t_27956e') }}</p>
            </div>
        </div>
    </section>
</div>

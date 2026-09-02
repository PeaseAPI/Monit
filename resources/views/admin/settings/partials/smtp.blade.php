<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.smtp.t_9b5683') }}</h3>
                <p class="settings-section-desc">{{ __('settings.smtp.t_e5d7d9') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.smtp.t_599417') }}</label>
                <input type="text" name="smtp_host" class="form-input" value="{{ old('smtp_host', $settings['smtp.smtp_host'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_7d4897') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_c76cfe') }}</label>
                <input type="number" name="smtp_port" class="form-input" value="{{ old('smtp_port', $settings['smtp.smtp_port'] ?? '465') }}" placeholder="465">
                <p class="form-hint">{{ __('settings.smtp.t_0a786f') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_75409f') }}</label>
                <select name="smtp_encryption" class="form-select">
                    @foreach (['ssl' => 'SSL（465）', 'tls' => 'TLS（587）', 'none' => __('settings.smtp.t_3c62b2')] as $v => $l)
                        <option value="{{ $v }}" {{ old('smtp_encryption', $settings['smtp.smtp_encryption'] ?? 'ssl') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.smtp.t_a33901') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.smtp.t_4f33d2') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.smtp.t_7295ca') }}</span>
                </span>
                <input type="checkbox" name="smtp_auth" value="1" class="input-toggle"
                    {{ filter_var($settings['smtp.smtp_auth'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_819767') }}</label>
                <input type="text" name="smtp_username" class="form-input" value="{{ old('smtp_username', $settings['smtp.smtp_username'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_f03f35') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_93b75d') }}</label>
                <input type="password" name="smtp_password" autocomplete="new-password" class="form-input" value="{{ old('smtp_password', $settings['smtp.smtp_password'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_81deeb') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.smtp.t_980fab') }}</h3>
                <p class="settings-section-desc">{{ __('settings.smtp.t_eb979f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.smtp.t_b85b21') }}</label>
                <input type="text" name="smtp_from_name" class="form-input" value="{{ old('smtp_from_name', $settings['smtp.smtp_from_name'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_feb011') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_5fbb35') }}</label>
                <input type="text" name="smtp_from_email" class="form-input" value="{{ old('smtp_from_email', $settings['smtp.smtp_from_email'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_e18051') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_2eacb0') }}</label>
                <input type="text" name="smtp_reply_to_name" class="form-input" value="{{ old('smtp_reply_to_name', $settings['smtp.smtp_reply_to_name'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_cb9fe1') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_b823e2') }}</label>
                <input type="text" name="smtp_reply_to" class="form-input" value="{{ old('smtp_reply_to', $settings['smtp.smtp_reply_to'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_b4ad7a') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_7b8347') }}</label>
                <input type="text" name="smtp_cc" class="form-input" value="{{ old('smtp_cc', $settings['smtp.smtp_cc'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_8ba5ad') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.smtp.t_d28177') }}</label>
                <input type="text" name="smtp_bcc" class="form-input" value="{{ old('smtp_bcc', $settings['smtp.smtp_bcc'] ?? '') }}">
                <p class="form-hint">{{ __('settings.smtp.t_8ba5ad') }}</p>
            </div>
        </div>
    </section>
</div>

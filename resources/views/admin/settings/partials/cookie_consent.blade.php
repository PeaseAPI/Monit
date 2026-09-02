<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.cookie_consent.t_0796ba') }}</h3>
                <p class="settings-section-desc">{{ __('settings.cookie_consent.t_f9f53f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cookie_consent.t_8aa106') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cookie_consent.t_d6d94c') }}</span>
                </span>
                <input type="checkbox" name="cookie_consent_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_0657d8') }}</label>
                <select name="cookie_consent_type" class="form-select">
                    @foreach (['simple' => __('settings.cookie_consent.t_f3465d'), 'detailed' => __('settings.cookie_consent.t_001227')] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_type', $settings['cookie_consent.cookie_consent_type'] ?? 'simple') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.cookie_consent.t_00b5dd') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_32c65d') }}</label>
                <input type="text" name="cookie_consent_title" class="form-input" value="{{ old('cookie_consent_title', $settings['cookie_consent.cookie_consent_title'] ?? __('settings.cookie_consent.t_1cd4fc')) }}">
                <p class="form-hint">{{ __('settings.cookie_consent.t_30ed0e') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_a80f36') }}</label>
                <textarea name="cookie_consent_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('cookie_consent_description', $settings['cookie_consent.cookie_consent_description'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.cookie_consent.t_545167') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_24bdc7') }}</label>
                <input type="text" name="cookie_consent_button_text" class="form-input" value="{{ old('cookie_consent_button_text', $settings['cookie_consent.cookie_consent_button_text'] ?? __('settings.cookie_consent.t_e61f2c')) }}">
                <p class="form-hint">{{ __('settings.cookie_consent.t_d53d3e') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.cookie_consent.t_6ba338') }}</h3>
                <p class="settings-section-desc">{{ __('settings.cookie_consent.t_aa3683') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cookie_consent.t_17ba9b') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cookie_consent.t_135fbc') }}</span>
                </span>
                <input type="checkbox" name="cookie_consent_necessary_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_necessary_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cookie_consent.t_a87fd2') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cookie_consent.t_1151c6') }}</span>
                </span>
                <input type="checkbox" name="cookie_consent_analytics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_analytics_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cookie_consent.t_610064') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cookie_consent.t_1c79e0') }}</span>
                </span>
                <input type="checkbox" name="cookie_consent_targeting_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_targeting_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.cookie_consent.t_a627f5') }}</h3>
                <p class="settings-section-desc">{{ __('settings.cookie_consent.t_895987') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_5aefca') }}</label>
                <select name="cookie_consent_layout" class="form-select">
                    @foreach (['bar' => __('settings.cookie_consent.t_0c843e'), 'box' => __('settings.cookie_consent.t_51ea17')] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_layout', $settings['cookie_consent.cookie_consent_layout'] ?? 'bar') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.cookie_consent.t_930f7e') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_200ea5') }}</label>
                <select name="cookie_consent_position_y" class="form-select">
                    @foreach (['top' => __('settings.cookie_consent.t_c94972'), 'bottom' => __('settings.cookie_consent.t_12c4c5')] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_position_y', $settings['cookie_consent.cookie_consent_position_y'] ?? 'bottom') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.cookie_consent.t_d0443e') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.cookie_consent.t_a3069a') }}</label>
                <select name="cookie_consent_position_x" class="form-select">
                    @foreach (['left' => __('settings.cookie_consent.t_a738a8'), 'center' => __('settings.cookie_consent.t_0bbc2e'), 'right' => __('settings.cookie_consent.t_fc0f19')] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_position_x', $settings['cookie_consent.cookie_consent_position_x'] ?? 'center') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.cookie_consent.t_ac9fe4') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.cookie_consent.t_56e5f5') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.cookie_consent.t_23ae37') }}</span>
                </span>
                <input type="checkbox" name="cookie_consent_logging_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_logging_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

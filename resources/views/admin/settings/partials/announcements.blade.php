<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.announcements.t_28604b') }}</h3>
                <p class="settings-section-desc">{{ __('settings.announcements.t_ffa9e2') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.announcements.t_40c83f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.announcements.t_1c9087') }}</span>
                </span>
                <input type="checkbox" name="announcements_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_8bbbe7') }}</label>
                <select name="announcements_type" class="form-select">
                    @foreach (['all' => __('settings.announcements.t_dbe48d'), 'guests' => __('settings.announcements.t_09714d'), 'users' => __('settings.announcements.t_0398eb')] as $v => $l)
                        <option value="{{ $v }}" {{ old('announcements_type', $settings['announcements.announcements_type'] ?? 'all') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.announcements.t_04695c') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_0dffc1') }}</label>
                <textarea name="announcements_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_content', $settings['announcements.announcements_content'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.announcements.t_ec6f56') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.announcements.t_d15369') }}</h3>
                <p class="settings-section-desc">{{ __('settings.announcements.t_9393c5') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.announcements.t_7b6648') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.announcements.t_b89a9f') }}</span>
                </span>
                <input type="checkbox" name="announcements_guests_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_guests_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_f376b3') }}</label>
                <textarea name="announcements_guests_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_guests_content', $settings['announcements.announcements_guests_content'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.announcements.t_ec6f56') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_7ec907') }}</label>
                <input type="text" name="announcements_guests_text_color" class="form-input" value="{{ old('announcements_guests_text_color', $settings['announcements.announcements_guests_text_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">{{ __('settings.announcements.t_87c504') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_4573a7') }}</label>
                <input type="text" name="announcements_guests_background_color" class="form-input" value="{{ old('announcements_guests_background_color', $settings['announcements.announcements_guests_background_color'] ?? '#6366f1') }}" placeholder="#6366f1">
                <p class="form-hint">{{ __('settings.announcements.t_9866dd') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.announcements.t_639629') }}</h3>
                <p class="settings-section-desc">{{ __('settings.announcements.t_a07d69') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.announcements.t_7ffe99') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.announcements.t_d1a9ea') }}</span>
                </span>
                <input type="checkbox" name="announcements_users_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_users_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_9454de') }}</label>
                <textarea name="announcements_users_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_users_content', $settings['announcements.announcements_users_content'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.announcements.t_ec6f56') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_7ec907') }}</label>
                <input type="text" name="announcements_users_text_color" class="form-input" value="{{ old('announcements_users_text_color', $settings['announcements.announcements_users_text_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">{{ __('settings.announcements.t_4f9c6c') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.announcements.t_4573a7') }}</label>
                <input type="text" name="announcements_users_background_color" class="form-input" value="{{ old('announcements_users_background_color', $settings['announcements.announcements_users_background_color'] ?? '#0ea5e9') }}" placeholder="#0ea5e9">
                <p class="form-hint">{{ __('settings.announcements.t_4f9c6c') }}</p>
            </div>
        </div>
    </section>
</div>

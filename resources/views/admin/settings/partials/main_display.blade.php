<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_92af61') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_0699e1') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_49fb0a') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_ec4d66') }}</span>
                </span>
                <input type="checkbox" name="display_pagination_when_no_pages" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_pagination_when_no_pages'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.main.t_8e6009') }}</label>
                <input type="number" name="default_results_per_page" class="form-input" value="{{ old('default_results_per_page', $settings['main.default_results_per_page'] ?? '25') }}" placeholder="25">
                <p class="form-hint">{{ __('settings.main.t_333e18') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_856cac') }}</label>
                <select name="default_order_type" class="form-select">
                    @foreach (['DESC' => __('settings.main.t_5093bc'), 'ASC' => __('settings.main.t_13cf78')] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_order_type', $settings['main.default_order_type'] ?? 'DESC') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.main.t_8e1bfa') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_1c0a9d') }}</label>
                <input type="number" name="avatar_size_limit" class="form-input" value="{{ old('avatar_size_limit', $settings['main.avatar_size_limit'] ?? '512') }}" placeholder="512">
                <p class="form-hint">{{ __('settings.main.t_00d19b') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_d70a43') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_49b1b5') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.main.t_240210') }}</label>
                <select name="default_theme_style" class="form-select">
                    @foreach (['light' => __('settings.main.t_48d0a0'), 'dark' => __('settings.main.t_41e8e8')] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_theme_style', $settings['main.default_theme_style'] ?? 'light') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.main.t_e59cd6') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_acda2d') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_0f3a73') }}</span>
                </span>
                <input type="checkbox" name="theme_style_change_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.theme_style_change_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_35340d') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_774e29') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.main.t_771f42') }}</label>
                <input type="number" name="chart_cache" class="form-input" value="{{ old('chart_cache', $settings['main.chart_cache'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.main.t_10ea1d') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_c94a37') }}</label>
                <input type="number" name="chart_days" class="form-input" value="{{ old('chart_days', $settings['main.chart_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.main.t_5b2e9a') }}</p>
            </div>
        </div>
    </section>
</div>

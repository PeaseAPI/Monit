<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.image_optimizer.t_4a656b') }}</h3>
                <p class="settings-section-desc">{{ __('settings.image_optimizer.t_14dd3e') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.image_optimizer.t_88c371') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.image_optimizer.t_85776b') }}</span>
                </span>
                <input type="checkbox" name="image_optimizer_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.image_optimizer.t_65c11e') }}</label>
                <select name="image_optimizer_provider" class="form-select">
                    @foreach (['local' => __('settings.image_optimizer.t_d51c3f'), 'imagerypro' => 'ImageryPro API'] as $v => $l)
                        <option value="{{ $v }}" {{ old('image_optimizer_provider', $settings['image_optimizer.image_optimizer_provider'] ?? 'local') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.image_optimizer.t_a09094') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.image_optimizer.t_3a7170') }}</label>
                <input type="number" name="image_optimizer_quality" class="form-input" value="{{ old('image_optimizer_quality', $settings['image_optimizer.image_optimizer_quality'] ?? '80') }}" placeholder="80">
                <p class="form-hint">{{ __('settings.image_optimizer.t_9f4aba') }}</p>
            </div>
            <div>
                <label class="form-label">ImageryPro API Key</label>
                <input type="text" name="image_optimizer_imagerypro_api_key" class="form-input" value="{{ old('image_optimizer_imagerypro_api_key', $settings['image_optimizer.image_optimizer_imagerypro_api_key'] ?? '') }}">
                <p class="form-hint">{{ __('settings.image_optimizer.t_8bd640') }}</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.image_optimizer.t_a9ef62') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.image_optimizer.t_aa5567') }}</span>
                </span>
                <input type="checkbox" name="image_optimizer_keep_original" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_keep_original'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.image_optimizer.t_3a8fac') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.image_optimizer.t_144d1b') }}</span>
                </span>
                <input type="checkbox" name="image_optimizer_auto_optimize" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_auto_optimize'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.image_optimizer.t_15cb7f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.image_optimizer.t_cf85d2') }}</span>
                </span>
                <input type="checkbox" name="image_optimizer_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_statistics_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

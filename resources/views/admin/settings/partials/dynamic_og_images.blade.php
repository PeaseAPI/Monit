<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.dynamic_og_images.t_83ba5d') }}</h3>
                <p class="settings-section-desc">{{ __('settings.dynamic_og_images.t_e7027e') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.dynamic_og_images.t_896af8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.dynamic_og_images.t_ccd0a5') }}</span>
                </span>
                <input type="checkbox" name="dynamic_og_images_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['dynamic_og_images.dynamic_og_images_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_f2b1fe') }}</label>
                <input type="text" name="dynamic_og_images_api_key" class="form-input" value="{{ old('dynamic_og_images_api_key', $settings['dynamic_og_images.dynamic_og_images_api_key'] ?? '') }}">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_5d221e') }}</p>
            </div>
            <div>
                <label class="form-label">ImageryPro API Key</label>
                <input type="text" name="dynamic_og_images_imagerypro_api_key" class="form-input" value="{{ old('dynamic_og_images_imagerypro_api_key', $settings['dynamic_og_images.dynamic_og_images_imagerypro_api_key'] ?? '') }}">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_889436') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_95cd50') }}</label>
                <input type="number" name="dynamic_og_images_quality" class="form-input" value="{{ old('dynamic_og_images_quality', $settings['dynamic_og_images.dynamic_og_images_quality'] ?? '80') }}" placeholder="80">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_157034') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_c8c12e') }}</label>
                <input type="text" name="dynamic_og_images_title" class="form-input" value="{{ old('dynamic_og_images_title', $settings['dynamic_og_images.dynamic_og_images_title'] ?? '') }}">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_9e3361') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_94e391') }}</label>
                <input type="text" name="dynamic_og_images_title_color" class="form-input" value="{{ old('dynamic_og_images_title_color', $settings['dynamic_og_images.dynamic_og_images_title_color'] ?? '#111827') }}" placeholder="#111827">
                <p class="form-hint">#RRGGBB</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_4573a7') }}</label>
                <input type="text" name="dynamic_og_images_background_color" class="form-input" value="{{ old('dynamic_og_images_background_color', $settings['dynamic_og_images.dynamic_og_images_background_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">#RRGGBB</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_a3c114') }}</label>
                <input type="number" name="dynamic_og_images_screenshot_image_border_radius" class="form-input" value="{{ old('dynamic_og_images_screenshot_image_border_radius', $settings['dynamic_og_images.dynamic_og_images_screenshot_image_border_radius'] ?? '12') }}" placeholder="12">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_43ba1a') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.dynamic_og_images.t_8df0f3') }}</label>
                <input type="number" name="dynamic_og_images_refresh_interval" class="form-input" value="{{ old('dynamic_og_images_refresh_interval', $settings['dynamic_og_images.dynamic_og_images_refresh_interval'] ?? '1440') }}" placeholder="1440">
                <p class="form-hint">{{ __('settings.dynamic_og_images.t_a44c12') }}</p>
            </div>
        </div>
    </section>
</div>

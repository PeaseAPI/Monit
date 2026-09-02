<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.content.t_c50d13') }}</h3>
                <p class="settings-section-desc">{{ __('settings.content.t_da4bd7') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_93982b') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_e37c54') }}</span>
                </span>
                <input type="checkbox" name="blog_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_302294') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_8b1c11') }}</span>
                </span>
                <input type="checkbox" name="blog_share_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_share_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_37dd3d') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_9d87d3') }}</span>
                </span>
                <input type="checkbox" name="blog_search_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_search_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_e5eded') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_b4327c') }}</span>
                </span>
                <input type="checkbox" name="blog_categories_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_categories_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_ebaf99') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_44d77f') }}</span>
                </span>
                <input type="checkbox" name="blog_popular_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_popular_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_0b2bb0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_17afbf') }}</span>
                </span>
                <input type="checkbox" name="blog_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_views_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_e30e6f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_ad0790') }}</span>
                </span>
                <input type="checkbox" name="blog_ratings_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_ratings_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.content.t_72e170') }}</label>
                <input type="number" name="blog_columns" class="form-input" value="{{ old('blog_columns', $settings['content.blog_columns'] ?? '1') }}" placeholder="1">
                <p class="form-hint">{{ __('settings.content.t_d638ef') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.content.t_59ceff') }}</h3>
                <p class="settings-section-desc">{{ __('settings.content.t_dd1748') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_12a8a9') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_668259') }}</span>
                </span>
                <input type="checkbox" name="pages_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_302294') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_fc8935') }}</span>
                </span>
                <input type="checkbox" name="pages_share_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_share_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_ebaf99') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_768789') }}</span>
                </span>
                <input type="checkbox" name="pages_popular_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_popular_widget_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_0b2bb0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_55b287') }}</span>
                </span>
                <input type="checkbox" name="pages_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_views_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.content.t_3eb855') }}</h3>
                <p class="settings-section-desc">{{ __('settings.content.t_388f00') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_9f2525') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_f97848') }}</span>
                </span>
                <input type="checkbox" name="broadcasts_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.broadcasts_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.content.t_200fe8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.content.t_d4d590') }}</span>
                </span>
                <input type="checkbox" name="broadcasts_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.broadcasts_statistics_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.content.t_30eaeb') }}</label>
                <input type="number" name="broadcasts_emails_per_cron" class="form-input" value="{{ old('broadcasts_emails_per_cron', $settings['content.broadcasts_emails_per_cron'] ?? '100') }}" placeholder="100">
                <p class="form-hint">{{ __('settings.content.t_2f3bf6') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.content.t_7789d4') }}</h3>
                <p class="settings-section-desc">{{ __('settings.content.t_b50940') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.content.t_1b3f1e') }}</label>
                <textarea name="index_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('index_html', $settings['content.index_html'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.content.t_8946f8') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.content.t_a1f0c9') }}</label>
                <textarea name="terms_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('terms_html', $settings['content.terms_html'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.content.t_7fa462') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.content.t_aab609') }}</label>
                <textarea name="privacy_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('privacy_html', $settings['content.privacy_html'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.content.t_27a7ec') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.content.t_a1614e') }}</label>
                <textarea name="imprint_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('imprint_html', $settings['content.imprint_html'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.content.t_60434d') }}</p>
            </div>
        </div>
    </section>
</div>

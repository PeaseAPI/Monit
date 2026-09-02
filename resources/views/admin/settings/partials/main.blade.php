<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_193288') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_04e1d3') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.main.t_b83cd9') }}</label>
                <input type="text" name="site_title" class="form-input" value="{{ old('site_title', $settings['main.site_title'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_f97b46') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_fefb77') }}</label>
                <textarea name="site_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('site_description', $settings['main.site_description'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.main.t_07be48') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_d69990') }}</label>
                <select name="default_language" class="form-select">
                    @foreach (['zh_CN' => __('settings.main.t_d688a3'), 'zh_TW' => __('settings.main.t_46c499'), 'en' => 'English', 'ru' => 'Русский', 'be' => 'Беларуская', 'ms' => 'Melayu'] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_language', $settings['main.default_language'] ?? 'zh_CN') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.main.t_73b702') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_d1acb4') }}</label>
                <input type="text" name="default_timezone" class="form-input" value="{{ old('default_timezone', $settings['main.default_timezone'] ?? 'Asia/Shanghai') }}" placeholder="Asia/Shanghai">
                <p class="form-hint">{{ __('settings.main.t_8b6f6b') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_40241a') }}</label>
                <input type="text" name="title_separator" class="form-input" value="{{ old('title_separator', $settings['main.title_separator'] ?? '·') }}" placeholder="·">
                <p class="form-hint">{{ __('settings.main.t_f1231c') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_c93c75') }}</label>
                <input type="url" name="index_url" class="form-input" value="{{ old('index_url', $settings['main.index_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_f4ab71') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_b96130') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_e77a08') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_4df241') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_6d6976') }}</span>
                </span>
                <input type="checkbox" name="registration_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.registration_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_ce66fc') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_f3e0d0') }}</span>
                </span>
                <input type="checkbox" name="api_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.api_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_aa3cfd') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_4209da') }}</span>
                </span>
                <input type="checkbox" name="whitelabel_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.whitelabel_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_7ca9f0') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_deb897') }}</span>
                </span>
                <input type="checkbox" name="force_https" value="1" class="input-toggle"
                    {{ filter_var($settings['main.force_https'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_d9c651') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_3e1961') }}</span>
                </span>
                <input type="checkbox" name="seo_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.seo_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_6ad6f1') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_3363f6') }}</span>
                </span>
                <input type="checkbox" name="iframe_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.iframe_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_1514c8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_318f99') }}</span>
                </span>
                <input type="checkbox" name="ai_crawlers_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.ai_crawlers_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_850ec1') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_67392f') }}</span>
                </span>
                <input type="checkbox" name="auto_language_detection_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.auto_language_detection_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_169bce') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_73023f') }}</span>
                </span>
                <input type="checkbox" name="breadcrumbs_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.breadcrumbs_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_c6354f') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_fc541f') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_e94b4c') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_899b9f') }}</span>
                </span>
                <input type="checkbox" name="display_index_plans" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_plans'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_125062') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_ec81ae') }}</span>
                </span>
                <input type="checkbox" name="display_index_testimonials" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_testimonials'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_f83cb4') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_54fc96') }}</span>
                </span>
                <input type="checkbox" name="display_index_faq" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_faq'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_5028d8') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_5b7e13') }}</span>
                </span>
                <input type="checkbox" name="display_index_latest_blog_posts" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_latest_blog_posts'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
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
                <h3 class="settings-section-title">{{ __('settings.main.t_972c4c') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_8c9e75') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.main.t_6bca1f') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.main.t_b31dfd') }}</span>
                </span>
                <input type="checkbox" name="maintenance_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.maintenance_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.main.t_d2a86c') }}</label>
                <input type="text" name="maintenance_title" class="form-input" value="{{ old('maintenance_title', $settings['main.maintenance_title'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_b34e22') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_aa48d5') }}</label>
                <textarea name="maintenance_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('maintenance_description', $settings['main.maintenance_description'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.main.t_099f36') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_24bdc7') }}</label>
                <input type="text" name="maintenance_button_text" class="form-input" value="{{ old('maintenance_button_text', $settings['main.maintenance_button_text'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_44c868') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_73b976') }}</label>
                <input type="url" name="maintenance_button_url" class="form-input" value="{{ old('maintenance_button_url', $settings['main.maintenance_button_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_1ea3ea') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.main.t_0e5b16') }}</h3>
                <p class="settings-section-desc">{{ __('settings.main.t_66cc8e') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">{{ __('settings.main.t_5c9843') }}</label>
                <select name="referrer_policy" class="form-select">
                    @foreach (['no-referrer' => 'no-referrer', 'origin' => 'origin', 'origin-when-cross-origin' => 'origin-when-cross-origin', 'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin', 'same-origin' => 'same-origin'] as $v => $l)
                        <option value="{{ $v }}" {{ old('referrer_policy', $settings['main.referrer_policy'] ?? 'strict-origin-when-cross-origin') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.main.t_b5bed2') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_29131b') }}</label>
                <input type="url" name="not_found_url" class="form-input" value="{{ old('not_found_url', $settings['main.not_found_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_9af865') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_8b5a67') }}</label>
                <input type="url" name="terms_and_conditions_url" class="form-input" value="{{ old('terms_and_conditions_url', $settings['main.terms_and_conditions_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_bf15bf') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_4c26f4') }}</label>
                <input type="url" name="privacy_policy_url" class="form-input" value="{{ old('privacy_policy_url', $settings['main.privacy_policy_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_5bb2b4') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_b8848d') }}</label>
                <input type="url" name="sitemap_url" class="form-input" value="{{ old('sitemap_url', $settings['main.sitemap_url'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_28bfb9') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.main.t_a542b0') }}</label>
                <input type="text" name="og_image" class="form-input" value="{{ old('og_image', $settings['main.og_image'] ?? '') }}">
                <p class="form-hint">{{ __('settings.main.t_b43665') }}</p>
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

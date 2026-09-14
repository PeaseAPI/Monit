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
</div>

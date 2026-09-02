<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.ads.title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.ads.desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.ads.enable') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.ads.enable_hint') }}</span>
                </span>
                <input type="checkbox" name="ads_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ads_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.ads.header_code') }}</label>
                <textarea name="ads_header" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('ads_header', $settings['ads.ads_header'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.ads.header_code_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.ads.footer_code') }}</label>
                <textarea name="ads_footer" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('ads_footer', $settings['ads.ads_footer'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.ads.footer_code_hint') }}</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.ads.ad_blocker_title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.ads.ad_blocker_desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.ads.ad_blocker_enable') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.ads.ad_blocker_enable_hint') }}</span>
                </span>
                <input type="checkbox" name="ad_blocker_detector_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ad_blocker_detector_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.ads.ad_blocker_lock') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.ads.ad_blocker_lock_hint') }}</span>
                </span>
                <input type="checkbox" name="ad_blocker_detector_lock_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ad_blocker_detector_lock_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.ads.ad_blocker_delay') }}</label>
                <input type="number" name="ad_blocker_detector_delay" class="form-input" value="{{ old('ad_blocker_detector_delay', $settings['ads.ad_blocker_detector_delay'] ?? '1000') }}" placeholder="1000">
                <p class="form-hint">{{ __('settings.ads.ad_blocker_delay_hint') }}</p>
            </div>
        </div>
    </section>
</div>

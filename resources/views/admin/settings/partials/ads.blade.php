<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">广告代码</h3>
                <p class="settings-section-desc">页头与页脚广告位（原版 ads）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用广告</span>
                    <span class="settings-field-row-hint">开启广告位输出</span>
                </span>
                <input type="checkbox" name="ads_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ads_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">页头广告代码</label>
                <textarea name="ads_header" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('ads_header', $settings['ads.ads_header'] ?? '') }}</textarea>
                <p class="form-hint">渲染在页面顶部</p>
            </div>
            <div>
                <label class="form-label">页脚广告代码</label>
                <textarea name="ads_footer" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('ads_footer', $settings['ads.ads_footer'] ?? '') }}</textarea>
                <p class="form-hint">渲染在页面底部</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">广告拦截检测（原版）</h3>
                <p class="settings-section-desc">检测访客广告拦截器</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用检测</span>
                    <span class="settings-field-row-hint">检测访客是否开启广告拦截</span>
                </span>
                <input type="checkbox" name="ad_blocker_detector_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ad_blocker_detector_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">拦截时锁定内容</span>
                    <span class="settings-field-row-hint">开启拦截器时阻止访问</span>
                </span>
                <input type="checkbox" name="ad_blocker_detector_lock_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['ads.ad_blocker_detector_lock_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">检测延迟（毫秒）</label>
                <input type="number" name="ad_blocker_detector_delay" class="form-input" value="{{ old('ad_blocker_detector_delay', $settings['ads.ad_blocker_detector_delay'] ?? '1000') }}" placeholder="1000">
                <p class="form-hint">页面加载多久后开始检测</p>
            </div>
        </div>
    </section>
</div>

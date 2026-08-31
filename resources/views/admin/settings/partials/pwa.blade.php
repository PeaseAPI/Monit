<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">PWA 基础</h3>
                <p class="settings-section-desc">可安装的渐进式应用（原版 pwa）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用 PWA</span>
                    <span class="settings-field-row-hint">生成 manifest 并支持安装到桌面</span>
                </span>
                <input type="checkbox" name="pwa_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">应用名称</label>
                <input type="text" name="pwa_name" class="form-input" value="{{ old('pwa_name', $settings['pwa.pwa_name'] ?? '') }}">
                <p class="form-hint">安装时显示的完整名称</p>
            </div>
            <div>
                <label class="form-label">短名称</label>
                <input type="text" name="pwa_short_name" class="form-input" value="{{ old('pwa_short_name', $settings['pwa.pwa_short_name'] ?? '') }}">
                <p class="form-hint">桌面图标下的短名称</p>
            </div>
            <div>
                <label class="form-label">应用描述</label>
                <textarea name="pwa_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('pwa_description', $settings['pwa.pwa_description'] ?? '') }}</textarea>
                <p class="form-hint">manifest 中的 description</p>
            </div>
            <div>
                <label class="form-label">启动地址</label>
                <input type="url" name="pwa_app_start_url" class="form-input" value="{{ old('pwa_app_start_url', $settings['pwa.pwa_app_start_url'] ?? '') }}">
                <p class="form-hint">应用启动时打开的页面</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">外观</h3>
                <p class="settings-section-desc">安装后的窗口样式（原版）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">主题色</label>
                <input type="text" name="pwa_theme_color" class="form-input" value="{{ old('pwa_theme_color', $settings['pwa.pwa_theme_color'] ?? '#6366f1') }}" placeholder="#6366f1">
                <p class="form-hint">地址栏等 UI 的颜色 #RRGGBB</p>
            </div>
            <div>
                <label class="form-label">背景色</label>
                <input type="text" name="pwa_background_color" class="form-input" value="{{ old('pwa_background_color', $settings['pwa.pwa_background_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">启动画面背景色</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">全屏模式</span>
                    <span class="settings-field-row-hint">安装后以全屏运行</span>
                </span>
                <input type="checkbox" name="pwa_is_fullscreen" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_is_fullscreen'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">动态启动画面</span>
                    <span class="settings-field-row-hint">根据设备生成启动图</span>
                </span>
                <input type="checkbox" name="pwa_dynamic_splash_screen" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_dynamic_splash_screen'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">安装横幅（原版）</h3>
                <p class="settings-section-desc">引导用户安装</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">显示安装横幅</span>
                    <span class="settings-field-row-hint">在页面显示安装引导条</span>
                </span>
                <input type="checkbox" name="pwa_display_install_bar" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_display_install_bar'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">访客也显示</span>
                    <span class="settings-field-row-hint">未登录访客同样显示安装引导</span>
                </span>
                <input type="checkbox" name="pwa_display_install_bar_for_guests" value="1" class="input-toggle"
                    {{ filter_var($settings['pwa.pwa_display_install_bar_for_guests'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">延迟（毫秒）</label>
                <input type="number" name="pwa_display_install_bar_delay" class="form-input" value="{{ old('pwa_display_install_bar_delay', $settings['pwa.pwa_display_install_bar_delay'] ?? '5000') }}" placeholder="5000">
                <p class="form-hint">页面加载多久后显示</p>
            </div>
            <div>
                <label class="form-label">最少浏览页数</label>
                <input type="number" name="pwa_display_install_bar_minimum_pageviews_count" class="form-input" value="{{ old('pwa_display_install_bar_minimum_pageviews_count', $settings['pwa.pwa_display_install_bar_minimum_pageviews_count'] ?? '3') }}" placeholder="3">
                <p class="form-hint">浏览多少页后显示</p>
            </div>
        </div>
    </section>
</div>

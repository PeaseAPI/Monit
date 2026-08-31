<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">动态 OG 图</h3>
                <p class="settings-section-desc">按页面自动生成分享图（原版 dynamic_og_images）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用动态 OG 图</span>
                    <span class="settings-field-row-hint">用截图服务为每个页面生成分享图</span>
                </span>
                <input type="checkbox" name="dynamic_og_images_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['dynamic_og_images.dynamic_og_images_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">截图服务 API Key</label>
                <input type="text" name="dynamic_og_images_api_key" class="form-input" value="{{ old('dynamic_og_images_api_key', $settings['dynamic_og_images.dynamic_og_images_api_key'] ?? '') }}">
                <p class="form-hint">渲染服务密钥</p>
            </div>
            <div>
                <label class="form-label">ImageryPro API Key</label>
                <input type="text" name="dynamic_og_images_imagerypro_api_key" class="form-input" value="{{ old('dynamic_og_images_imagerypro_api_key', $settings['dynamic_og_images.dynamic_og_images_imagerypro_api_key'] ?? '') }}">
                <p class="form-hint">使用 ImageryPro 时的密钥</p>
            </div>
            <div>
                <label class="form-label">图片质量</label>
                <input type="number" name="dynamic_og_images_quality" class="form-input" value="{{ old('dynamic_og_images_quality', $settings['dynamic_og_images.dynamic_og_images_quality'] ?? '80') }}" placeholder="80">
                <p class="form-hint">生成图片质量 1-100</p>
            </div>
            <div>
                <label class="form-label">默认标题</label>
                <input type="text" name="dynamic_og_images_title" class="form-input" value="{{ old('dynamic_og_images_title', $settings['dynamic_og_images.dynamic_og_images_title'] ?? '') }}">
                <p class="form-hint">OG 图上的默认标题</p>
            </div>
            <div>
                <label class="form-label">标题颜色</label>
                <input type="text" name="dynamic_og_images_title_color" class="form-input" value="{{ old('dynamic_og_images_title_color', $settings['dynamic_og_images.dynamic_og_images_title_color'] ?? '#111827') }}" placeholder="#111827">
                <p class="form-hint">#RRGGBB</p>
            </div>
            <div>
                <label class="form-label">背景颜色</label>
                <input type="text" name="dynamic_og_images_background_color" class="form-input" value="{{ old('dynamic_og_images_background_color', $settings['dynamic_og_images.dynamic_og_images_background_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">#RRGGBB</p>
            </div>
            <div>
                <label class="form-label">截图圆角</label>
                <input type="number" name="dynamic_og_images_screenshot_image_border_radius" class="form-input" value="{{ old('dynamic_og_images_screenshot_image_border_radius', $settings['dynamic_og_images.dynamic_og_images_screenshot_image_border_radius'] ?? '12') }}" placeholder="12">
                <p class="form-hint">预览截图的圆角像素</p>
            </div>
            <div>
                <label class="form-label">刷新间隔</label>
                <input type="number" name="dynamic_og_images_refresh_interval" class="form-input" value="{{ old('dynamic_og_images_refresh_interval', $settings['dynamic_og_images.dynamic_og_images_refresh_interval'] ?? '1440') }}" placeholder="1440">
                <p class="form-hint">重新生成图片的间隔（分钟）</p>
            </div>
        </div>
    </section>
</div>

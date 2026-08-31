<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">图片优化</h3>
                <p class="settings-section-desc">上传图片自动压缩（原版 image_optimizer）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用图片优化</span>
                    <span class="settings-field-row-hint">上传图片时自动压缩转 WebP</span>
                </span>
                <input type="checkbox" name="image_optimizer_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">优化引擎</label>
                <select name="image_optimizer_provider" class="form-select">
                    @foreach (['local' => '本地（GD/Imagick）', 'imagerypro' => 'ImageryPro API'] as $v => $l)
                        <option value="{{ $v }}" {{ old('image_optimizer_provider', $settings['image_optimizer.image_optimizer_provider'] ?? 'local') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">本地或 ImageryPro 云端</p>
            </div>
            <div>
                <label class="form-label">质量</label>
                <input type="number" name="image_optimizer_quality" class="form-input" value="{{ old('image_optimizer_quality', $settings['image_optimizer.image_optimizer_quality'] ?? '80') }}" placeholder="80">
                <p class="form-hint">压缩质量 1-100，越高越清晰</p>
            </div>
            <div>
                <label class="form-label">ImageryPro API Key</label>
                <input type="text" name="image_optimizer_imagerypro_api_key" class="form-input" value="{{ old('image_optimizer_imagerypro_api_key', $settings['image_optimizer.image_optimizer_imagerypro_api_key'] ?? '') }}">
                <p class="form-hint">选择 ImageryPro 引擎时必填</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">保留原图</span>
                    <span class="settings-field-row-hint">同时保存未压缩的原始文件</span>
                </span>
                <input type="checkbox" name="image_optimizer_keep_original" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_keep_original'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">自动优化</span>
                    <span class="settings-field-row-hint">上传后立即优化，无需手动触发</span>
                </span>
                <input type="checkbox" name="image_optimizer_auto_optimize" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_auto_optimize'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">优化统计</span>
                    <span class="settings-field-row-hint">记录节省的存储空间</span>
                </span>
                <input type="checkbox" name="image_optimizer_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['image_optimizer.image_optimizer_statistics_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

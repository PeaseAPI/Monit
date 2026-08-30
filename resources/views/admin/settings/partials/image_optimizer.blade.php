<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置图片优化插件（规格书 §14.9：Image Optimizer 插件）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="image_optimizer_is_enabled" value="1" {{ ($settings['image_optimizer.image_optimizer_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用图片优化
        </label>
        <div>
            <label class="form-label">压缩质量 (1-100)</label>
            <input type="number" name="image_optimizer_quality" class="form-input w-32" min="1" max="100" value="{{ old('image_optimizer_quality', $settings['image_optimizer.image_optimizer_quality'] ?? 75) }}">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="image_optimizer_keep_original" value="1" {{ ($settings['image_optimizer.image_optimizer_keep_original'] ?? true) ? 'checked' : '' }}>
            保留原始图片
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="image_optimizer_auto_optimize" value="1" {{ ($settings['image_optimizer.image_optimizer_auto_optimize'] ?? true) ? 'checked' : '' }}>
            上传时自动优化
        </label>
    </div>
</div>


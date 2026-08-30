<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置 PWA 渐进式 Web 应用插件（规格书 §14.6）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="pwa_is_enabled" value="1" {{ ($settings['pwa.pwa_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用 PWA
        </label>
        <div>
            <label class="form-label">应用名称</label>
            <input type="text" name="pwa_name" class="form-input" value="{{ old('pwa_name', $settings['pwa.pwa_name'] ?? config('app.name')) }}">
        </div>
        <div>
            <label class="form-label">短名称</label>
            <input type="text" name="pwa_short_name" class="form-input" value="{{ old('pwa_short_name', $settings['pwa.pwa_short_name'] ?? 'Monit') }}">
        </div>
        <div>
            <label class="form-label">应用描述</label>
            <input type="text" name="pwa_description" class="form-input" value="{{ old('pwa_description', $settings['pwa.pwa_description'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">主题色</label>
            <input type="color" name="pwa_theme_color" class="form-input w-20 h-10" value="{{ old('pwa_theme_color', $settings['pwa.pwa_theme_color'] ?? '#4f46e5') }}">
        </div>
        <div>
            <label class="form-label">背景色</label>
            <input type="color" name="pwa_background_color" class="form-input w-20 h-10" value="{{ old('pwa_background_color', $settings['pwa.pwa_background_color'] ?? '#ffffff') }}">
        </div>
    </div>
</div>


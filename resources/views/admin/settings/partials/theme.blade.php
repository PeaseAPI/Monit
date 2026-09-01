<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置主题与外观设置（规格书 §6.5：UI/前端全面重设计）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">默认主题</label>
            <select name="theme" class="form-select">
                <option value="light" {{ ($settings['theme.theme'] ?? 'light') === 'light' ? 'selected' : '' }}>浅色模式</option>
                <option value="dark" {{ ($settings['theme.theme'] ?? '') === 'dark' ? 'selected' : '' }}>深色模式</option>
                <option value="system" {{ ($settings['theme.theme'] ?? '') === 'system' ? 'selected' : '' }}>跟随系统</option>
            </select>
        </div>
        <div>
            <label class="form-label">主色调</label>
            <input type="color" name="primary_color" class="form-input w-20 h-10" value="{{ old('primary_color', $settings['theme.primary_color'] ?? '#4f46e5') }}">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="white_label_is_enabled" value="1" {{ filter_var($settings['theme.white_label_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        {{ __('admin.white_label_hint') }}
        </label>
    </div>
</div>


<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.theme.desc') }}</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">{{ __('settings.theme.default_theme') }}</label>
            <select name="theme" class="form-select">
                <option value="light" {{ ($settings['theme.theme'] ?? 'light') === 'light' ? 'selected' : '' }}>{{ __('settings.theme.light') }}</option>
                <option value="dark" {{ ($settings['theme.theme'] ?? '') === 'dark' ? 'selected' : '' }}>{{ __('settings.theme.dark') }}</option>
                <option value="system" {{ ($settings['theme.theme'] ?? '') === 'system' ? 'selected' : '' }}>{{ __('settings.theme.system') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('settings.theme.primary_color') }}</label>
            <input type="color" name="primary_color" class="form-input w-20 h-10" value="{{ old('primary_color', $settings['theme.primary_color'] ?? '#4f46e5') }}">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="white_label_is_enabled" value="1" {{ filter_var($settings['theme.white_label_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                        {{ __('admin.white_label_hint') }}
        </label>
    </div>
</div>


<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.custom_images.desc') }}</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">{{ __('settings.custom_images.logo_url') }}</label>
            <input type="url" name="logo" class="form-input w-full" placeholder="https://cdn.example.com/logo.png"
                   value="{{ old('logo', $settings['custom_images.logo'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.custom_images.logo_url_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.custom_images.favicon_url') }}</label>
            <input type="url" name="favicon" class="form-input w-full" placeholder="https://cdn.example.com/favicon.ico"
                   value="{{ old('favicon', $settings['custom_images.favicon'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.custom_images.favicon_url_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.custom_images.og_image') }}</label>
            <input type="url" name="og_image" class="form-input w-full" placeholder="https://cdn.example.com/og.png"
                   value="{{ old('og_image', $settings['custom_images.og_image'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.custom_images.og_image_hint') }}</p>
        </div>
    </div>
</div>

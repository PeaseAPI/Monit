<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('admin.branding_desc') }}</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">{{ __('admin.branding_site_name') }}</label>
            <input type="text" name="site_name" class="form-input w-full" placeholder="Monit"
                   value="{{ old('site_name', $settings['branding.site_name'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_site_name_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_logo_url') }}</label>
            <input type="url" name="logo_url" class="form-input w-full" placeholder="https://cdn.example.com/logo.png"
                   value="{{ old('logo_url', $settings['branding.logo_url'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_logo_url_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_logo_dark_url') }}</label>
            <input type="url" name="logo_dark_url" class="form-input w-full" placeholder="https://cdn.example.com/logo-dark.png"
                   value="{{ old('logo_dark_url', $settings['branding.logo_dark_url'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_logo_dark_url_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_favicon_url') }}</label>
            <input type="url" name="favicon_url" class="form-input w-full" placeholder="https://cdn.example.com/favicon.ico"
                   value="{{ old('favicon_url', $settings['branding.favicon_url'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_favicon_url_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_primary_color') }}</label>
            <div class="flex items-center gap-3">
                <input type="color" name="primary_color" class="h-10 w-16 cursor-pointer rounded-lg border border-zinc-300 bg-white p-1"
                       value="{{ old('primary_color', $settings['branding.primary_color'] ?? '#4f46e5') }}">
                <span class="text-xs text-zinc-400">{{ __('admin.branding_primary_color_hint') }}</span>
            </div>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_landing_theme') }}</label>
            <select name="landing_theme" class="form-input w-full">
                @foreach (['default' => __('admin.branding_theme_default')] as $theme => $label)
                    <option value="{{ $theme }}" @selected(old('landing_theme', $settings['branding.landing_theme'] ?? 'default') === $theme)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_landing_theme_hint') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="checkbox" id="show_landing_plans" name="show_landing_plans" value="1"
                   @checked(($settings['branding.show_landing_plans'] ?? 'true') !== 'false')>
            <label for="show_landing_plans" class="text-sm">{{ __('admin.branding_show_landing_plans') }}</label>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_landing_hero_title') }}</label>
            <input type="text" name="landing_hero_title" class="form-input w-full"
                   placeholder="{{ __('landing.hero_title') }}"
                   value="{{ old('landing_hero_title', $settings['branding.landing_hero_title'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_hero_override_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_landing_hero_subtitle') }}</label>
            <textarea name="landing_hero_subtitle" rows="2" class="form-input w-full"
                      placeholder="{{ __('landing.hero_subtitle') }}">{{ old('landing_hero_subtitle', $settings['branding.landing_hero_subtitle'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_footer_icp') }}</label>
            <input type="text" name="footer_icp" class="form-input w-full" placeholder="京ICP备XXXXXXXX号-1"
                   value="{{ old('footer_icp', $settings['branding.footer_icp'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_footer_icp_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('admin.branding_footer_custom_html') }}</label>
            <textarea name="footer_custom_html" rows="6" class="form-input w-full font-mono" placeholder="&lt;script&gt;…&lt;/script&gt; &nbsp;|&nbsp; &lt;a href=…&gt;…&lt;/a&gt;">{{ old('footer_custom_html', $settings['branding.footer_custom_html'] ?? '') }}</textarea>
            <p class="mt-1 text-xs text-zinc-400">{{ __('admin.branding_footer_custom_html_hint') }}</p>
        </div>
    </div>
</div>

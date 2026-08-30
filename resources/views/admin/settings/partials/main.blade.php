{{-- 主设置（规格书 §6.3.1） --}}
<div class="space-y-4">
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.site_title') }}</label>
        <input type="text" name="site_title" value="{{ $settings['main.site_title'] ?? config('app.name') }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.site_url') }}</label>
        <input type="url" name="site_url" value="{{ $settings['main.site_url'] ?? url('/') }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.default_language') }}</label>
        <select name="default_language" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm">
            <option value="zh_CN" {{ ($settings['main.default_language'] ?? 'zh_CN') === 'zh_CN' ? 'selected' : ''}}>简体中文</option>
            <option value="en" {{ ($settings['main.default_language'] ?? '') === 'en' ? 'selected' : ''}}>English</option>
        </select></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="registration_is_enabled" value="1" {{ ($settings['main.registration_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.registration_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="email_verification_is_enabled" value="1" {{ ($settings['main.email_verification_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.email_verification_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="maintenance_is_enabled" value="1" {{ ($settings['main.maintenance_is_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.maintenance_mode') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="api_is_enabled" value="1" {{ ($settings['main.api_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.api_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="white_labeling_is_enabled" value="1" {{ ($settings['main.white_labeling_is_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.white_labeling') }}</label></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.seo_title') }}</label>
        <input type="text" name="seo_title" value="{{ $settings['main.seo_title'] ?? '' }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.seo_description') }}</label>
        <textarea name="seo_description" rows="3" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm">{{ $settings['main.seo_description'] ?? '' }}</textarea></div>
</div>

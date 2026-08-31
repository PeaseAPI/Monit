{{-- 地图服务设置（maps）：供应商选择（内置 SVG / 百度 / 谷歌）+ Key 配置 --}}
<div class="space-y-5">
    <p class="text-sm leading-relaxed text-zinc-500">{{ __('admin.maps_desc') }}</p>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('admin.maps_provider') }}</label>
        <select name="provider" class="w-full rounded-xl border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <option value="none" {{ ($settings['maps.provider'] ?? 'none') === 'none' ? 'selected' : '' }}>{{ __('admin.maps_provider_none') }}</option>
            <option value="baidu" {{ ($settings['maps.provider'] ?? 'none') === 'baidu' ? 'selected' : '' }}>{{ __('admin.maps_provider_baidu') }}</option>
            <option value="google" {{ ($settings['maps.provider'] ?? 'none') === 'google' ? 'selected' : '' }}>{{ __('admin.maps_provider_google') }}</option>
        </select>
        <p class="mt-1.5 text-xs text-zinc-400">{{ __('admin.maps_provider_hint') }}</p>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('admin.maps_baidu_key') }}</label>
        <input type="text" name="baidu_key" value="{{ $settings['maps.baidu_key'] ?? '' }}" placeholder="AK（浏览器端密钥）"
            class="w-full rounded-xl border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" autocomplete="off">
        <p class="mt-1.5 text-xs text-zinc-400">{{ __('admin.maps_baidu_key_hint') }}</p>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-zinc-700">{{ __('admin.maps_google_key') }}</label>
        <input type="text" name="google_key" value="{{ $settings['maps.google_key'] ?? '' }}" placeholder="API Key"
            class="w-full rounded-xl border-zinc-300 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" autocomplete="off">
        <p class="mt-1.5 text-xs text-zinc-400">{{ __('admin.maps_google_key_hint') }}</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-700">
        {{ __('admin.maps_note') }}
    </div>
</div>

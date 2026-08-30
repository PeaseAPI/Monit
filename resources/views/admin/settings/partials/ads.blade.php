<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置广告管理设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="ads_is_enabled" value="1" {{ ($settings['ads.ads_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用广告
        </label>
        <div>
            <label class="form-label">页头广告代码</label>
            <textarea name="ads_header" class="form-input w-full font-mono" rows="4">{{ old('ads_header', $settings['ads.ads_header'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">页脚广告代码</label>
            <textarea name="ads_footer" class="form-input w-full font-mono" rows="4">{{ old('ads_footer', $settings['ads.ads_footer'] ?? '') }}</textarea>
        </div>
    </div>
</div>


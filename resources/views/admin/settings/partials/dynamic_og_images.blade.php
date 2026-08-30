<div class="space-y-6">
    <p class="text-sm text-zinc-500">动态 OG 图片设置（规格书 §6.3.5 / §14.7：/dynamic-og-images/{type}/{id}）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="dynamic_og_images_is_enabled" value="1" {{ ($settings['dynamic_og_images.dynamic_og_images_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用动态 OG 图片生成
        </label>
    </div>
</div>

<div class="space-y-6">
    <p class="text-sm text-zinc-500">品牌图片设置：覆盖默认 Logo、Favicon 与社交分享图（URL 或上传后的路径）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">Logo 图片地址</label>
            <input type="url" name="logo" class="form-input w-full" placeholder="https://cdn.example.com/logo.png"
                   value="{{ old('logo', $settings['custom_images.logo'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">留空使用默认 Logo</p>
        </div>
        <div>
            <label class="form-label">Favicon 地址</label>
            <input type="url" name="favicon" class="form-input w-full" placeholder="https://cdn.example.com/favicon.ico"
                   value="{{ old('favicon', $settings['custom_images.favicon'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">留空使用默认 Favicon</p>
        </div>
        <div>
            <label class="form-label">社交分享 OG 图片</label>
            <input type="url" name="og_image" class="form-input w-full" placeholder="https://cdn.example.com/og.png"
                   value="{{ old('og_image', $settings['custom_images.og_image'] ?? '') }}">
            <p class="mt-1 text-xs text-zinc-400">用于 og:image 与 Twitter Card，建议 1200×630</p>
        </div>
    </div>
</div>

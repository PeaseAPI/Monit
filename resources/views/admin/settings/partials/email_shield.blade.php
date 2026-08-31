{{-- 邮箱防采集（email_shield）：@email_shield 指令的混淆输出开关（规格 §14.9） --}}
<div class="space-y-6">
    <p class="text-sm text-zinc-500">开启后 {{ '@email_shield' }} / {{ '@email_shield_link' }} 指令对邮箱地址做混淆输出，防止被爬虫采集；关闭时原样输出。</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_shield_is_enabled" value="1" {{ ($settings['email_shield.email_shield_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用邮箱防采集（Email Shield）
        </label>
    </div>
</div>

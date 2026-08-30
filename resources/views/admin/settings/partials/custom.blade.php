<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置自定义代码与内容设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">自定义 HEAD JS 代码</label>
            <textarea name="custom_head_js" class="form-input w-full font-mono" rows="6" placeholder="&lt;script&gt;...&lt;/script&gt;">{{ old('custom_head_js', $settings['custom.custom_head_js'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">自定义 HEAD CSS 样式</label>
            <textarea name="custom_head_css" class="form-input w-full font-mono" rows="6" placeholder="&lt;style&gt;...&lt;/style&gt;">{{ old('custom_head_css', $settings['custom.custom_head_css'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">自定义 FOOTER JS 代码</label>
            <textarea name="custom_footer_js" class="form-input w-full font-mono" rows="6" placeholder="&lt;script&gt;...&lt;/script&gt;">{{ old('custom_footer_js', $settings['custom.custom_footer_js'] ?? '') }}</textarea>
        </div>
    </div>
</div>


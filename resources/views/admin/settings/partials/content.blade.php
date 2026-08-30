<div class="space-y-6">
    <p class="text-sm text-zinc-500">公共页面内容覆盖：自定义首页 HTML 与法律页面内容（支持 HTML）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">首页自定义 HTML</label>
            <textarea name="index_html" class="form-input w-full font-mono" rows="8" placeholder="&lt;section&gt;...&lt;/section&gt;">{{ old('index_html', $settings['content.index_html'] ?? '') }}</textarea>
            <p class="mt-1 text-xs text-zinc-400">留空使用默认落地页</p>
        </div>
        <div>
            <label class="form-label">服务条款页内容</label>
            <textarea name="terms_html" class="form-input w-full font-mono" rows="8" placeholder="&lt;h1&gt;Terms&lt;/h1&gt;...">{{ old('terms_html', $settings['content.terms_html'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">隐私政策页内容</label>
            <textarea name="privacy_html" class="form-input w-full font-mono" rows="8" placeholder="&lt;h1&gt;Privacy&lt;/h1&gt;...">{{ old('privacy_html', $settings['content.privacy_html'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">页脚印记（Imprint）内容</label>
            <textarea name="imprint_html" class="form-input w-full font-mono" rows="6" placeholder="&lt;p&gt;Company info...&lt;/p&gt;">{{ old('imprint_html', $settings['content.imprint_html'] ?? '') }}</textarea>
        </div>
    </div>
</div>

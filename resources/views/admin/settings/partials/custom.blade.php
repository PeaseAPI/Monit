<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">自定义代码注入</h3>
                <p class="settings-section-desc">在全站关键位置注入代码（原版 custom）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">HEAD JS</label>
                <textarea name="custom_head_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_head_js', $settings['custom.custom_head_js'] ?? '') }}</textarea>
                <p class="form-hint">注入到 &lt;head&gt; 底部的 JavaScript</p>
            </div>
            <div>
                <label class="form-label">HEAD CSS</label>
                <textarea name="custom_head_css" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_head_css', $settings['custom.custom_head_css'] ?? '') }}</textarea>
                <p class="form-hint">注入到 &lt;head&gt; 底部的 CSS</p>
            </div>
            <div>
                <label class="form-label">页脚 JS</label>
                <textarea name="custom_footer_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_footer_js', $settings['custom.custom_footer_js'] ?? '') }}</textarea>
                <p class="form-hint">注入到 &lt;/body&gt; 前的 JavaScript</p>
            </div>
            <div>
                <label class="form-label">欢迎页 JS</label>
                <textarea name="custom_welcome_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_welcome_js', $settings['custom.custom_welcome_js'] ?? '') }}</textarea>
                <p class="form-hint">新用户首次进入面板时执行</p>
            </div>
            <div>
                <label class="form-label">支付成功页 JS</label>
                <textarea name="custom_pay_thank_you_js" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_pay_thank_you_js', $settings['custom.custom_pay_thank_you_js'] ?? '') }}</textarea>
                <p class="form-hint">支付完成页执行的代码（转化追踪）</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">正文内容</h3>
                <p class="settings-section-desc">页面主体注入（原版 body_content）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">BODY 内容</label>
                <textarea name="custom_body_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('custom_body_content', $settings['custom.custom_body_content'] ?? '') }}</textarea>
                <p class="form-hint">注入到页面主体的 HTML 片段</p>
            </div>
        </div>
    </section>
</div>

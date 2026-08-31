<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">博客</h3>
                <p class="settings-section-desc">博客模块开关与展示</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用博客</span>
                    <span class="settings-field-row-hint">关闭后前台隐藏博客入口</span>
                </span>
                <input type="checkbox" name="blog_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">分享按钮</span>
                    <span class="settings-field-row-hint">博文页显示社交分享按钮</span>
                </span>
                <input type="checkbox" name="blog_share_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_share_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">搜索小组件</span>
                    <span class="settings-field-row-hint">博客侧栏显示搜索框</span>
                </span>
                <input type="checkbox" name="blog_search_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_search_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">分类小组件</span>
                    <span class="settings-field-row-hint">博客侧栏显示分类列表</span>
                </span>
                <input type="checkbox" name="blog_categories_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_categories_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">热门小组件</span>
                    <span class="settings-field-row-hint">博客侧栏显示热门文章</span>
                </span>
                <input type="checkbox" name="blog_popular_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_popular_widget_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">浏览计数</span>
                    <span class="settings-field-row-hint">显示文章浏览量</span>
                </span>
                <input type="checkbox" name="blog_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_views_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">文章评分</span>
                    <span class="settings-field-row-hint">允许读者为文章打分</span>
                </span>
                <input type="checkbox" name="blog_ratings_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.blog_ratings_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">列表列数</label>
                <input type="number" name="blog_columns" class="form-input" value="{{ old('blog_columns', $settings['content.blog_columns'] ?? '1') }}" placeholder="1">
                <p class="form-hint">博客列表每行列数（1-4）</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">页面</h3>
                <p class="settings-section-desc">自定义页面模块</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用页面</span>
                    <span class="settings-field-row-hint">关闭后前台隐藏自定义页面</span>
                </span>
                <input type="checkbox" name="pages_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">分享按钮</span>
                    <span class="settings-field-row-hint">页面显示社交分享按钮</span>
                </span>
                <input type="checkbox" name="pages_share_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_share_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">热门小组件</span>
                    <span class="settings-field-row-hint">显示热门页面列表</span>
                </span>
                <input type="checkbox" name="pages_popular_widget_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_popular_widget_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">浏览计数</span>
                    <span class="settings-field-row-hint">显示页面浏览量</span>
                </span>
                <input type="checkbox" name="pages_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.pages_views_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">广播</h3>
                <p class="settings-section-desc">站内信 / 邮件广播</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用广播</span>
                    <span class="settings-field-row-hint">允许管理员发送站内广播</span>
                </span>
                <input type="checkbox" name="broadcasts_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.broadcasts_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">广播统计</span>
                    <span class="settings-field-row-hint">记录广播的阅读与点击数据</span>
                </span>
                <input type="checkbox" name="broadcasts_statistics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['content.broadcasts_statistics_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">每轮发送上限</label>
                <input type="number" name="broadcasts_emails_per_cron" class="form-input" value="{{ old('broadcasts_emails_per_cron', $settings['content.broadcasts_emails_per_cron'] ?? '100') }}" placeholder="100">
                <p class="form-hint">每次定时任务最多发送的邮件数</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">静态内容</h3>
                <p class="settings-section-desc">落地页与法务页面 HTML</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">自定义首页 HTML</label>
                <textarea name="index_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('index_html', $settings['content.index_html'] ?? '') }}</textarea>
                <p class="form-hint">覆盖落地页主体内容（谨慎使用）</p>
            </div>
            <div>
                <label class="form-label">服务条款 HTML</label>
                <textarea name="terms_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('terms_html', $settings['content.terms_html'] ?? '') }}</textarea>
                <p class="form-hint">服务条款页面正文</p>
            </div>
            <div>
                <label class="form-label">隐私政策 HTML</label>
                <textarea name="privacy_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('privacy_html', $settings['content.privacy_html'] ?? '') }}</textarea>
                <p class="form-hint">隐私政策页面正文</p>
            </div>
            <div>
                <label class="form-label">版权 imprint HTML</label>
                <textarea name="imprint_html" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('imprint_html', $settings['content.imprint_html'] ?? '') }}</textarea>
                <p class="form-hint">页脚版权信息正文</p>
            </div>
        </div>
    </section>
</div>

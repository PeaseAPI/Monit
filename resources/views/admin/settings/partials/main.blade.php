<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">站点基础</h3>
                <p class="settings-section-desc">站点名称、默认语言与时区</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">站点标题</label>
                <input type="text" name="site_title" class="form-input" value="{{ old('site_title', $settings['main.site_title'] ?? '') }}">
                <p class="form-hint">浏览器标签与全站展示的名称</p>
            </div>
            <div>
                <label class="form-label">站点描述</label>
                <textarea name="site_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('site_description', $settings['main.site_description'] ?? '') }}</textarea>
                <p class="form-hint">用于 SEO meta description 与分享卡片</p>
            </div>
            <div>
                <label class="form-label">默认语言</label>
                <select name="default_language" class="form-select">
                    @foreach (['zh_CN' => '简体中文', 'zh_TW' => '繁體中文', 'en' => 'English', 'ru' => 'Русский', 'be' => 'Беларуская', 'ms' => 'Melayu'] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_language', $settings['main.default_language'] ?? 'zh_CN') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">未登录访客的界面语言</p>
            </div>
            <div>
                <label class="form-label">默认时区</label>
                <input type="text" name="default_timezone" class="form-input" value="{{ old('default_timezone', $settings['main.default_timezone'] ?? 'Asia/Shanghai') }}" placeholder="Asia/Shanghai">
                <p class="form-hint">如 Asia/Shanghai（PHP 时区标识）</p>
            </div>
            <div>
                <label class="form-label">标题分隔符</label>
                <input type="text" name="title_separator" class="form-input" value="{{ old('title_separator', $settings['main.title_separator'] ?? '·') }}" placeholder="·">
                <p class="form-hint">页面 title 与站点名之间的分隔符</p>
            </div>
            <div>
                <label class="form-label">首页地址</label>
                <input type="url" name="index_url" class="form-input" value="{{ old('index_url', $settings['main.index_url'] ?? '') }}">
                <p class="form-hint">自定义落地页地址，留空使用默认 /</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">功能开关</h3>
                <p class="settings-section-desc">全站级功能启停</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">开放注册</span>
                    <span class="settings-field-row-hint">关闭后新用户无法注册账号</span>
                </span>
                <input type="checkbox" name="registration_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.registration_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">开放 API</span>
                    <span class="settings-field-row-hint">允许用户使用 API 接口拉取统计数据</span>
                </span>
                <input type="checkbox" name="api_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.api_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">白标模式</span>
                    <span class="settings-field-row-hint">隐藏产品品牌标识，便于代理商定制</span>
                </span>
                <input type="checkbox" name="whitelabel_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.whitelabel_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">强制 HTTPS</span>
                    <span class="settings-field-row-hint">将全部 HTTP 请求 301 跳转到 HTTPS</span>
                </span>
                <input type="checkbox" name="force_https" value="1" class="input-toggle"
                    {{ filter_var($settings['main.force_https'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">搜索引擎收录</span>
                    <span class="settings-field-row-hint">允许搜索引擎索引公开页面（原版 se_indexing）</span>
                </span>
                <input type="checkbox" name="seo_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.seo_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">允许 iframe 嵌入</span>
                    <span class="settings-field-row-hint">关闭后通过 X-Frame-Options 防点击劫持</span>
                </span>
                <input type="checkbox" name="iframe_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.iframe_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">允许 AI 爬虫</span>
                    <span class="settings-field-row-hint">允许 GPTBot 等 AI 爬虫抓取公开内容</span>
                </span>
                <input type="checkbox" name="ai_crawlers_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.ai_crawlers_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">自动语言检测</span>
                    <span class="settings-field-row-hint">按浏览器语言自动切换界面</span>
                </span>
                <input type="checkbox" name="auto_language_detection_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.auto_language_detection_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">显示面包屑</span>
                    <span class="settings-field-row-hint">在用户面板顶部显示路径导航</span>
                </span>
                <input type="checkbox" name="breadcrumbs_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.breadcrumbs_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">首页区块</h3>
                <p class="settings-section-desc">控制落地页展示的内容板块</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">展示套餐定价</span>
                    <span class="settings-field-row-hint">在落地页显示价格区块</span>
                </span>
                <input type="checkbox" name="display_index_plans" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_plans'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">展示用户评价</span>
                    <span class="settings-field-row-hint">在落地页显示评价区</span>
                </span>
                <input type="checkbox" name="display_index_testimonials" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_testimonials'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">展示常见问题</span>
                    <span class="settings-field-row-hint">在落地页显示 FAQ 区块</span>
                </span>
                <input type="checkbox" name="display_index_faq" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_faq'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">展示最新博客</span>
                    <span class="settings-field-row-hint">在落地页显示最新博文列表</span>
                </span>
                <input type="checkbox" name="display_index_latest_blog_posts" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_index_latest_blog_posts'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">列表与分页</h3>
                <p class="settings-section-desc">后台与前台列表的通用行为</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">仅一页也显示分页</span>
                    <span class="settings-field-row-hint">关闭后单页时隐藏分页条</span>
                </span>
                <input type="checkbox" name="display_pagination_when_no_pages" value="1" class="input-toggle"
                    {{ filter_var($settings['main.display_pagination_when_no_pages'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">每页条数</label>
                <input type="number" name="default_results_per_page" class="form-input" value="{{ old('default_results_per_page', $settings['main.default_results_per_page'] ?? '25') }}" placeholder="25">
                <p class="form-hint">列表页默认每页数量（5-100）</p>
            </div>
            <div>
                <label class="form-label">默认排序</label>
                <select name="default_order_type" class="form-select">
                    @foreach (['DESC' => '最新优先', 'ASC' => '最早优先'] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_order_type', $settings['main.default_order_type'] ?? 'DESC') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">列表默认排序方向（原版 default_order_type）</p>
            </div>
            <div>
                <label class="form-label">头像大小上限（KB）</label>
                <input type="number" name="avatar_size_limit" class="form-input" value="{{ old('avatar_size_limit', $settings['main.avatar_size_limit'] ?? '512') }}" placeholder="512">
                <p class="form-hint">用户头像上传大小上限</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">维护模式</h3>
                <p class="settings-section-desc">全站维护时的只读提示页</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用维护模式</span>
                    <span class="settings-field-row-hint">开启后前台显示维护页，管理员不受影响</span>
                </span>
                <input type="checkbox" name="maintenance_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.maintenance_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">维护标题</label>
                <input type="text" name="maintenance_title" class="form-input" value="{{ old('maintenance_title', $settings['main.maintenance_title'] ?? '') }}">
                <p class="form-hint">如「系统升级中」</p>
            </div>
            <div>
                <label class="form-label">维护说明</label>
                <textarea name="maintenance_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('maintenance_description', $settings['main.maintenance_description'] ?? '') }}</textarea>
                <p class="form-hint">向访客解释维护原因与预计恢复时间</p>
            </div>
            <div>
                <label class="form-label">按钮文字</label>
                <input type="text" name="maintenance_button_text" class="form-input" value="{{ old('maintenance_button_text', $settings['main.maintenance_button_text'] ?? '') }}">
                <p class="form-hint">维护页按钮展示文字</p>
            </div>
            <div>
                <label class="form-label">按钮链接</label>
                <input type="url" name="maintenance_button_url" class="form-input" value="{{ old('maintenance_button_url', $settings['main.maintenance_button_url'] ?? '') }}">
                <p class="form-hint">维护页按钮跳转地址</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">SEO 与链接</h3>
                <p class="settings-section-desc">搜索引擎与法务页面地址</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">Referrer 策略</label>
                <select name="referrer_policy" class="form-select">
                    @foreach (['no-referrer' => 'no-referrer', 'origin' => 'origin', 'origin-when-cross-origin' => 'origin-when-cross-origin', 'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin', 'same-origin' => 'same-origin'] as $v => $l)
                        <option value="{{ $v }}" {{ old('referrer_policy', $settings['main.referrer_policy'] ?? 'strict-origin-when-cross-origin') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">控制外链携带的 Referrer 信息</p>
            </div>
            <div>
                <label class="form-label">404 跳转地址</label>
                <input type="url" name="not_found_url" class="form-input" value="{{ old('not_found_url', $settings['main.not_found_url'] ?? '') }}">
                <p class="form-hint">留空使用内置 404 页</p>
            </div>
            <div>
                <label class="form-label">服务条款地址</label>
                <input type="url" name="terms_and_conditions_url" class="form-input" value="{{ old('terms_and_conditions_url', $settings['main.terms_and_conditions_url'] ?? '') }}">
                <p class="form-hint">自定义服务条款页面链接</p>
            </div>
            <div>
                <label class="form-label">隐私政策地址</label>
                <input type="url" name="privacy_policy_url" class="form-input" value="{{ old('privacy_policy_url', $settings['main.privacy_policy_url'] ?? '') }}">
                <p class="form-hint">自定义隐私政策页面链接</p>
            </div>
            <div>
                <label class="form-label">站点地图地址</label>
                <input type="url" name="sitemap_url" class="form-input" value="{{ old('sitemap_url', $settings['main.sitemap_url'] ?? '') }}">
                <p class="form-hint">自定义 sitemap 链接</p>
            </div>
            <div>
                <label class="form-label">默认分享图</label>
                <input type="text" name="og_image" class="form-input" value="{{ old('og_image', $settings['main.og_image'] ?? '') }}">
                <p class="form-hint">Open Graph 默认图片 URL</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">主题样式</h3>
                <p class="settings-section-desc">默认配色与样式切换</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">默认样式</label>
                <select name="default_theme_style" class="form-select">
                    @foreach (['light' => '浅色', 'dark' => '深色'] as $v => $l)
                        <option value="{{ $v }}" {{ old('default_theme_style', $settings['main.default_theme_style'] ?? 'light') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">全站默认明暗风格</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">允许用户切换样式</span>
                    <span class="settings-field-row-hint">用户可在账户设置里自行切换明暗</span>
                </span>
                <input type="checkbox" name="theme_style_change_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['main.theme_style_change_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">图表缓存</h3>
                <p class="settings-section-desc">统计图表的缓存策略</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">图表缓存分钟数</label>
                <input type="number" name="chart_cache" class="form-input" value="{{ old('chart_cache', $settings['main.chart_cache'] ?? '30') }}" placeholder="30">
                <p class="form-hint">0 为不缓存；建议 30-60 分钟</p>
            </div>
            <div>
                <label class="form-label">图表默认天数</label>
                <input type="number" name="chart_days" class="form-input" value="{{ old('chart_days', $settings['main.chart_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">统计图表默认展示的天数范围</p>
            </div>
        </div>
    </section>
</div>

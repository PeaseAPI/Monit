<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">数据采集</h3>
                <p class="settings-section-desc">访客数据的存储与最小化</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">存储完整 IP</span>
                    <span class="settings-field-row-hint">关闭时仅保存 IP 哈希（隐私友好）</span>
                </span>
                <input type="checkbox" name="ip_storage_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.ip_storage_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">回放最短时长（秒）</label>
                <input type="number" name="sessions_replays_minimum_duration" class="form-input" value="{{ old('sessions_replays_minimum_duration', $settings['analytics.sessions_replays_minimum_duration'] ?? '0') }}" placeholder="0">
                <p class="form-hint">小于该时长的会话不录制回放</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">统计脚本缓存</span>
                    <span class="settings-field-row-hint">启用统计脚本浏览器缓存</span>
                </span>
                <input type="checkbox" name="pixel_cache" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.pixel_cache'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">暴露统计标识</span>
                    <span class="settings-field-row-hint">允许访客查看自己的统计 ID（调试用）</span>
                </span>
                <input type="checkbox" name="pixel_exposed_identifier" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.pixel_exposed_identifier'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">邮件数据提醒</span>
                    <span class="settings-field-row-hint">向站长发送异常流量提醒邮件</span>
                </span>
                <input type="checkbox" name="email_notices_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.email_notices_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">域名</h3>
                <p class="settings-section-desc">追踪域名与自定义域</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用域名模块</span>
                    <span class="settings-field-row-hint">允许用户绑定自有域名</span>
                </span>
                <input type="checkbox" name="domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">附加域名</span>
                    <span class="settings-field-row-hint">允许一个站点配置多个域名</span>
                </span>
                <input type="checkbox" name="additional_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.additional_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">主域名</span>
                    <span class="settings-field-row-hint">启用主域名单独解析（原版 main_domain）</span>
                </span>
                <input type="checkbox" name="main_domain_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.main_domain_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">自定义主域 IP</label>
                <input type="text" name="domains_custom_main_ip" class="form-input" value="{{ old('domains_custom_main_ip', $settings['analytics.domains_custom_main_ip'] ?? '') }}">
                <p class="form-hint">主域名指向的自定义 IP 地址</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">追踪黑名单</h3>
                <p class="settings-section-desc">不采集的来源</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">域名黑名单</label>
                <textarea name="blacklisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_domains', $settings['analytics.blacklisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">每行一个域名，命中则不采集</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">示例与演示</h3>
                <p class="settings-section-desc">安装向导示例数据</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">示例站点地址</label>
                <input type="url" name="example_url" class="form-input" value="{{ old('example_url', $settings['analytics.example_url'] ?? '') }}">
                <p class="form-hint">安装向导中演示用的目标站点</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">模块开关</h3>
                <p class="settings-section-desc">各分析模块的可用性</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">邮件报告</span>
                    <span class="settings-field-row-hint">用户可订阅定期统计邮件</span>
                </span>
                <input type="checkbox" name="email_reports_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.email_reports_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">批注</span>
                    <span class="settings-field-row-hint">在统计图上添加备注批注</span>
                </span>
                <input type="checkbox" name="annotations_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.annotations_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">会话回放</span>
                    <span class="settings-field-row-hint">录制并回放访客操作</span>
                </span>
                <input type="checkbox" name="sessions_replays_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.sessions_replays_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">热图</span>
                    <span class="settings-field-row-hint">点击热图与滚动热图</span>
                </span>
                <input type="checkbox" name="websites_heatmaps_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.websites_heatmaps_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">仪表视图</span>
                    <span class="settings-field-row-hint">自定义统计仪表盘视图</span>
                </span>
                <input type="checkbox" name="dashboard_views_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.dashboard_views_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">自定义域名统计</span>
                    <span class="settings-field-row-hint">按域名维度聚合统计</span>
                </span>
                <input type="checkbox" name="custom_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.custom_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">额外域名</span>
                    <span class="settings-field-row-hint">允许添加额外统计域名</span>
                </span>
                <input type="checkbox" name="extra_domains_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.extra_domains_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">出站点击</span>
                    <span class="settings-field-row-hint">记录外链点击事件</span>
                </span>
                <input type="checkbox" name="outbound_clicks_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['analytics.outbound_clicks_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

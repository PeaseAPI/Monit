<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">功能开关</h3>
                <p class="settings-section-desc">SEO 审计 / 工具中心 / Sitemap 与域名监控的总闸（整合后台 seo 组全部功能设置）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">SEO 审计</span>
                    <span class="settings-field-row-hint">关闭后站点分析、公共目录、定时复审全部停用</span>
                </span>
                <input type="checkbox" name="audits_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.audits_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">SEO 工具中心</span>
                    <span class="settings-field-row-hint">关闭后 /tools 全部入口停用（含登录用户）</span>
                </span>
                <input type="checkbox" name="tools_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.tools_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">访客可用工具中心</span>
                    <span class="settings-field-row-hint">关闭后仅登录用户可使用（SeoGuestAccess 中间件）</span>
                </span>
                <input type="checkbox" name="tools_guest_access" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.tools_guest_access'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">访客月度工具配额</label>
                <input type="number" min="0" name="tools_guest_monthly_limit" class="form-input" value="{{ old('tools_guest_monthly_limit', $settings['seo.tools_guest_monthly_limit'] ?? 20) }}">
                <p class="form-hint">0 表示禁止访客使用；-1 不限制（按 uploader_key 每月计数）</p>
            </div>
            <div>
                <label class="form-label">停用指定工具</label>
                <textarea name="seo_disabled_tools" rows="3" class="form-input w-full font-mono text-[13px]">{{ old('seo_disabled_tools', $settings['seo.seo_disabled_tools'] ?? '') }}</textarea>
                <p class="form-hint">每行一个工具 slug（如 ahrefs_domain_rating），留空表示全部启用</p>
            </div>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">审计引擎</h3>
                <p class="settings-section-desc">AuditEngine 抓取参数（复审 / 手动分析共用）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">抓取超时（秒）</label>
                <input type="number" min="5" max="120" name="seo_request_timeout" class="form-input" value="{{ old('seo_request_timeout', $settings['seo.seo_request_timeout'] ?? 20) }}">
                <p class="form-hint">目标页 / robots / sitemap 探测的单请求超时</p>
            </div>
            <div>
                <label class="form-label">抓取 User-Agent</label>
                <input type="text" name="seo_request_user_agent" class="form-input" value="{{ old('seo_request_user_agent', $settings['seo.seo_request_user_agent'] ?? 'Mozilla/5.0 (compatible; MonitBot/1.0)') }}">
                <p class="form-hint">留空使用默认 MonitBot 标识</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">失败自动重试</span>
                    <span class="settings-field-row-hint">目标站 5xx / 超时时静默重试一次（反爬偶发失败兜底）</span>
                </span>
                <input type="checkbox" name="seo_double_check" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.seo_double_check'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">重试等待（秒）</label>
                <input type="number" min="1" max="10" name="seo_double_check_wait" class="form-input" value="{{ old('seo_double_check_wait', $settings['seo.seo_double_check_wait'] ?? 2) }}">
                <p class="form-hint">首次失败后等待多久发起重试</p>
            </div>
        </div>
    </section>

    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">监控与保留</h3>
                <p class="settings-section-desc">到期预警档位与历史快照保留策略</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">域名到期预警档位（天）</label>
                <input type="text" name="domain_monitor_alert_days" class="form-input" value="{{ old('domain_monitor_alert_days', $settings['seo.domain_monitor_alert_days'] ?? '30,7,1') }}">
                <p class="form-hint">逗号分隔；距到期天数命中档位当天发送通知（如 30,7,1）</p>
            </div>
            <div>
                <label class="form-label">归档保留天数（兜底）</label>
                <input type="number" min="0" max="3650" name="archives_retention_days" class="form-input" value="{{ old('archives_retention_days', $settings['seo.archives_retention_days'] ?? 30) }}">
                <p class="form-hint">用户套餐未指定 seo_history_retention_days 与游客审计的保留期；0 = 永久保留</p>
            </div>
        </div>
    </section>
</div>

            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">Sitemap 变更监控</span>
                    <span class="settings-field-row-hint">定时任务 monit:seo-sitemaps-check 的站点级总闸</span>
                </span>
                <input type="checkbox" name="sitemap_monitor_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.sitemap_monitor_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">域名到期监控</span>
                    <span class="settings-field-row-hint">定时任务 monit:seo-domains-monitor 的站点级总闸</span>
                </span>
                <input type="checkbox" name="domain_monitor_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['seo.domain_monitor_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>

<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">定时任务</h3>
                <p class="settings-section-desc">Cron 密钥与任务开关（原版 cron）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">Cron 密钥</label>
                <input type="text" name="cron_key" class="form-input" value="{{ old('cron_key', $settings['cron.cron_key'] ?? '') }}">
                <p class="form-hint">调用定时任务的密钥参数，防未授权触发</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">邮件报告任务</span>
                    <span class="settings-field-row-hint">按用户订阅发送统计邮件</span>
                </span>
                <input type="checkbox" name="cron_email_reports" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_email_reports'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">广播发送任务</span>
                    <span class="settings-field-row-hint">批量发送广播邮件</span>
                </span>
                <input type="checkbox" name="cron_broadcasts" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_broadcasts'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">推送发送任务</span>
                    <span class="settings-field-row-hint">批量发送 Web 推送</span>
                </span>
                <input type="checkbox" name="cron_push_notifications" value="1" class="input-toggle"
                    {{ filter_var($settings['cron.cron_push_notifications'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

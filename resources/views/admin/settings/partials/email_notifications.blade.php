<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">事件通知</h3>
                <p class="settings-section-desc">指定事件发生时给管理员发邮件（原版 email_notifications）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新用户注册</span>
                    <span class="settings-field-row-hint">有新用户注册时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_new_user" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_user'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新支付订单</span>
                    <span class="settings-field-row-hint">收到新支付时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_new_payment" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_payment'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新站点创建</span>
                    <span class="settings-field-row-hint">用户创建新站点时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_new_website" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_website'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">用户注销</span>
                    <span class="settings-field-row-hint">用户删除账号时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_delete_user" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_delete_user'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新域名绑定</span>
                    <span class="settings-field-row-hint">用户绑定新域名时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_new_domain" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_domain'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">联系我们</span>
                    <span class="settings-field-row-hint">收到联系表单留言时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_contact" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_contact'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">推广提现申请</span>
                    <span class="settings-field-row-hint">有新的提现申请时通知</span>
                </span>
                <input type="checkbox" name="email_notifications_new_affiliate_withdrawal" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_new_affiliate_withdrawal'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">套餐到期提醒</span>
                    <span class="settings-field-row-hint">用户套餐临期时发送提醒</span>
                </span>
                <input type="checkbox" name="email_notifications_user_plan_expiry_reminder" value="1" class="input-toggle"
                    {{ filter_var($settings['email_notifications.email_notifications_user_plan_expiry_reminder'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

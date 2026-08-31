<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">站内通知</h3>
                <p class="settings-section-desc">面向用户/管理员的站内通知（原版 internal_notifications）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用站内通知</span>
                    <span class="settings-field-row-hint">总开关</span>
                </span>
                <input type="checkbox" name="internal_notifications_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">用户通知</span>
                    <span class="settings-field-row-hint">向用户推送站内通知</span>
                </span>
                <input type="checkbox" name="internal_notifications_users_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_users_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">管理员通知</span>
                    <span class="settings-field-row-hint">向管理员推送站内通知</span>
                </span>
                <input type="checkbox" name="internal_notifications_admins_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_admins_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">通知事件</h3>
                <p class="settings-section-desc">触发站内通知的事件</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新用户注册</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_user" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_user'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">用户注销</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_delete_user" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_delete_user'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新订阅者</span>
                    <span class="settings-field-row-hint">邮件列表新增订阅时</span>
                </span>
                <input type="checkbox" name="internal_notifications_new_newsletter_subscriber" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_newsletter_subscriber'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">新支付订单</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_payment" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_payment'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">推广提现申请</span>
                    <span class="settings-field-row-hint"></span>
                </span>
                <input type="checkbox" name="internal_notifications_new_affiliate_withdrawal" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_new_affiliate_withdrawal'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">支付成功</span>
                    <span class="settings-field-row-hint">用户侧支付成功通知</span>
                </span>
                <input type="checkbox" name="internal_notifications_payment_success" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_payment_success'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">套餐到期</span>
                    <span class="settings-field-row-hint">用户套餐到期通知</span>
                </span>
                <input type="checkbox" name="internal_notifications_plan_expiry" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_plan_expiry'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">配额用尽</span>
                    <span class="settings-field-row-hint">用量达到套餐上限时</span>
                </span>
                <input type="checkbox" name="internal_notifications_limit_reached" value="1" class="input-toggle"
                    {{ filter_var($settings['internal_notifications.internal_notifications_limit_reached'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

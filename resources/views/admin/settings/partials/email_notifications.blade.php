<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置邮件通知设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_notifications_new_user" value="1" {{ ($settings['email_notifications.email_notifications_new_user'] ?? true) ? 'checked' : '' }}>
            新用户注册通知管理员
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_notifications_new_payment" value="1" {{ ($settings['email_notifications.email_notifications_new_payment'] ?? true) ? 'checked' : '' }}>
            新支付通知管理员
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_notifications_new_website" value="1" {{ ($settings['email_notifications.email_notifications_new_website'] ?? true) ? 'checked' : '' }}>
            新网站创建通知管理员
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_notifications_user_plan_expiry_reminder" value="1" {{ ($settings['email_notifications.email_notifications_user_plan_expiry_reminder'] ?? true) ? 'checked' : '' }}>
            用户套餐到期提醒
        </label>
    </div>
</div>

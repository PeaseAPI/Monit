<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置站内通知设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="internal_notifications_is_enabled" value="1" {{ ($settings['internal_notifications.internal_notifications_is_enabled'] ?? true) ? 'checked' : '' }}>
            启用站内通知
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="internal_notifications_payment_success" value="1" {{ ($settings['internal_notifications.internal_notifications_payment_success'] ?? true) ? 'checked' : '' }}>
            支付成功通知
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="internal_notifications_plan_expiry" value="1" {{ ($settings['internal_notifications.internal_notifications_plan_expiry'] ?? true) ? 'checked' : '' }}>
            套餐到期通知
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="internal_notifications_limit_reached" value="1" {{ ($settings['internal_notifications.internal_notifications_limit_reached'] ?? true) ? 'checked' : '' }}>
            配额达到上限通知
        </label>
    </div>
</div>

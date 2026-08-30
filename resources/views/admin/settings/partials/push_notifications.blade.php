<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置推送通知插件（规格书 §14.5）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="push_notifications_is_enabled" value="1" {{ ($settings['push_notifications.push_notifications_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用推送通知
        </label>
        <div>
            <label class="form-label">VAPID 主题邮箱</label>
            <input type="email" name="push_notifications_vapid_subject" class="form-input" value="{{ old('push_notifications_vapid_subject', $settings['push_notifications.push_notifications_vapid_subject'] ?? 'mailto:admin@example.com') }}">
        </div>
        <div>
            <label class="form-label">VAPID 公钥</label>
            <input type="text" name="push_notifications_public_key" class="form-input" value="{{ old('push_notifications_public_key', $settings['push_notifications.push_notifications_public_key'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">VAPID 私钥</label>
            <input type="password" name="push_notifications_private_key" class="form-input" value="{{ old('push_notifications_private_key', $settings['push_notifications.push_notifications_private_key'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">订阅者上限</label>
            <input type="number" name="push_notifications_subscribers_limit" class="form-input w-32" value="{{ old('push_notifications_subscribers_limit', $settings['push_notifications.push_notifications_subscribers_limit'] ?? 1000) }}">
        </div>
        <div>
            <label class="form-label">推送活动上限</label>
            <input type="number" name="push_notifications_campaigns_limit" class="form-input w-32" value="{{ old('push_notifications_campaigns_limit', $settings['push_notifications.push_notifications_campaigns_limit'] ?? 10) }}">
        </div>
    </div>
</div>


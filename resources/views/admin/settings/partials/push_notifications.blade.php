<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">Web 推送基础</h3>
                <p class="settings-section-desc">浏览器推送通知（原版 push_notifications）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用 Web 推送</span>
                    <span class="settings-field-row-hint">总开关，需配置 VAPID 密钥</span>
                </span>
                <input type="checkbox" name="push_notifications_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.push_notifications_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">访客订阅</span>
                    <span class="settings-field-row-hint">允许未登录访客订阅推送</span>
                </span>
                <input type="checkbox" name="push_notifications_guests_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.push_notifications_guests_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">VAPID 公钥</label>
                <input type="text" name="push_notifications_public_key" class="form-input" value="{{ old('push_notifications_public_key', $settings['push_notifications.push_notifications_public_key'] ?? '') }}">
                <p class="form-hint">Web Push VAPID Public Key</p>
            </div>
            <div>
                <label class="form-label">VAPID 私钥</label>
                <input type="password" name="push_notifications_private_key" autocomplete="new-password" class="form-input" value="{{ old('push_notifications_private_key', $settings['push_notifications.push_notifications_private_key'] ?? '') }}">
                <p class="form-hint">Web Push VAPID Private Key</p>
            </div>
            <div>
                <label class="form-label">VAPID Subject</label>
                <input type="text" name="push_notifications_vapid_subject" class="form-input" value="{{ old('push_notifications_vapid_subject', $settings['push_notifications.push_notifications_vapid_subject'] ?? '') }}">
                <p class="form-hint">mailto: 或 https: 联系方式</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">订阅引导（原版）</h3>
                <p class="settings-section-desc">主动引导访客订阅</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用订阅引导</span>
                    <span class="settings-field-row-hint">自动弹出订阅授权提示</span>
                </span>
                <input type="checkbox" name="ask_to_subscribe_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['push_notifications.ask_to_subscribe_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">延迟（毫秒）</label>
                <input type="number" name="ask_to_subscribe_delay" class="form-input" value="{{ old('ask_to_subscribe_delay', $settings['push_notifications.ask_to_subscribe_delay'] ?? '5000') }}" placeholder="5000">
                <p class="form-hint">页面加载多久后弹出</p>
            </div>
            <div>
                <label class="form-label">最少浏览页数</label>
                <input type="number" name="ask_to_subscribe_delay_minimum_pageviews_count" class="form-input" value="{{ old('ask_to_subscribe_delay_minimum_pageviews_count', $settings['push_notifications.ask_to_subscribe_delay_minimum_pageviews_count'] ?? '3') }}" placeholder="3">
                <p class="form-hint">访客浏览多少页后才提示</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">发送配额</h3>
                <p class="settings-section-desc">定时任务批量发送限制（原版）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">订阅者上限</label>
                <input type="number" name="push_notifications_subscribers_limit" class="form-input" value="{{ old('push_notifications_subscribers_limit', $settings['push_notifications.push_notifications_subscribers_limit'] ?? '1000') }}" placeholder="1000">
                <p class="form-hint">每个用户最多订阅者数量</p>
            </div>
            <div>
                <label class="form-label">活动上限</label>
                <input type="number" name="push_notifications_campaigns_limit" class="form-input" value="{{ old('push_notifications_campaigns_limit', $settings['push_notifications.push_notifications_campaigns_limit'] ?? '10') }}" placeholder="10">
                <p class="form-hint">每个用户最多推送活动数</p>
            </div>
            <div>
                <label class="form-label">每轮发送量</label>
                <input type="number" name="notifications_per_cron" class="form-input" value="{{ old('notifications_per_cron', $settings['push_notifications.notifications_per_cron'] ?? '100') }}" placeholder="100">
                <p class="form-hint">单次定时任务最多发送条数</p>
            </div>
            <div>
                <label class="form-label">批次大小</label>
                <input type="number" name="notifications_per_cron_batch" class="form-input" value="{{ old('notifications_per_cron_batch', $settings['push_notifications.notifications_per_cron_batch'] ?? '50') }}" placeholder="50">
                <p class="form-hint">每批处理的订阅数量</p>
            </div>
            <div>
                <label class="form-label">并发批次数</label>
                <input type="number" name="notifications_per_cron_batch_concurrently" class="form-input" value="{{ old('notifications_per_cron_batch_concurrently', $settings['push_notifications.notifications_per_cron_batch_concurrently'] ?? '5') }}" placeholder="5">
                <p class="form-hint">同时处理的批次数</p>
            </div>
        </div>
    </section>
</div>

<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置 Webhook 回调设置（规格书 §6.3.1：开始/结束 webhook）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">支付成功 Webhook URL</label>
            <input type="url" name="webhook_payment_success_url" class="form-input w-full" value="{{ old('webhook_payment_success_url', $settings['webhooks.webhook_payment_success_url'] ?? '') }}" placeholder="https://your-server.com/webhook/payment-success">
        </div>
        <div>
            <label class="form-label">支付失败 Webhook URL</label>
            <input type="url" name="webhook_payment_failure_url" class="form-input w-full" value="{{ old('webhook_payment_failure_url', $settings['webhooks.webhook_payment_failure_url'] ?? '') }}" placeholder="https://your-server.com/webhook/payment-failure">
        </div>
        <div>
            <label class="form-label">用户注册 Webhook URL</label>
            <input type="url" name="webhook_user_register_url" class="form-input w-full" value="{{ old('webhook_user_register_url', $settings['webhooks.webhook_user_register_url'] ?? '') }}" placeholder="https://your-server.com/webhook/user-register">
        </div>
        <div>
            <label class="form-label">用户删除 Webhook URL</label>
            <input type="url" name="webhook_user_delete_url" class="form-input w-full" value="{{ old('webhook_user_delete_url', $settings['webhooks.webhook_user_delete_url'] ?? '') }}" placeholder="https://your-server.com/webhook/user-delete">
        </div>
    </div>
</div>


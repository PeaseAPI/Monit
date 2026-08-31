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

{{-- 原版对标补充：事件开关与密钥（AltumCode webhooks） --}}
<section class="settings-section mt-6">
    <div class="settings-section-header">
        <div>
            <h3 class="settings-section-title">事件订阅（原版对标）</h3>
            <p class="settings-section-desc">勾选后随请求推送对应事件给上述端点</p>
        </div>
    </div>
    <div class="settings-section-body">
        <div>
            <label class="form-label">签名密钥</label>
            <input type="text" name="webhooks_secret_key" class="form-input font-mono" value="{{ old('webhooks_secret_key', $settings['webhooks.webhooks_secret_key'] ?? '') }}" placeholder="webhook 签名密钥（HMAC）">
            <p class="form-hint">原版 secret_key：接收方用其校验 X-Signature 头</p>
        </div>
        <div>
            <label class="form-label">等待响应的域名</label>
            <textarea name="wait_for_response_domains" rows="3" class="form-input w-full font-mono text-[13px]">{{ old('wait_for_response_domains', $settings['webhooks.wait_for_response_domains'] ?? '') }}</textarea>
            <p class="form-hint">每行一个域名：这些端点会同步等待 2xx 响应（原版）</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @php
                $whEvents = [
                    'webhooks_user_new' => ['新用户注册', true],
                    'webhooks_user_update' => ['用户资料更新', false],
                    'webhooks_user_delete' => ['用户注销', true],
                    'webhooks_payment_new' => ['新支付订单', true],
                    'webhooks_code_redeemed' => ['优惠码核销', false],
                    'webhooks_contact' => ['联系我们提交', false],
                    'webhooks_cron_start' => ['定时任务开始', false],
                    'webhooks_cron_end' => ['定时任务结束', false],
                    'webhooks_domain_new' => ['新域名绑定', false],
                    'webhooks_domain_update' => ['域名状态变更', false],
                ];
            @endphp
            @foreach ($whEvents as $name => [$label, $default])
                <label class="settings-field-row">
                    <span class="min-w-0">
                        <span class="settings-field-row-label">{{ $label }}</span>
                    </span>
                    <input type="checkbox" name="{{ $name }}" value="1" class="input-toggle"
                        {{ filter_var($settings['webhooks.'.$name] ?? $default, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                </label>
            @endforeach
        </div>
    </div>
</section>



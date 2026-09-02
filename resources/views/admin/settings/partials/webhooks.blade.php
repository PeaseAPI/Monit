<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.webhooks.t_a446ca') }}</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_3c2519') }}</label>
            <input type="url" name="webhook_payment_success_url" class="form-input w-full" value="{{ old('webhook_payment_success_url', $settings['webhooks.webhook_payment_success_url'] ?? '') }}" placeholder="https://your-server.com/webhook/payment-success">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_557d6c') }}</label>
            <input type="url" name="webhook_payment_failure_url" class="form-input w-full" value="{{ old('webhook_payment_failure_url', $settings['webhooks.webhook_payment_failure_url'] ?? '') }}" placeholder="https://your-server.com/webhook/payment-failure">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_b5e3d0') }}</label>
            <input type="url" name="webhook_user_register_url" class="form-input w-full" value="{{ old('webhook_user_register_url', $settings['webhooks.webhook_user_register_url'] ?? '') }}" placeholder="https://your-server.com/webhook/user-register">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_259c90') }}</label>
            <input type="url" name="webhook_user_delete_url" class="form-input w-full" value="{{ old('webhook_user_delete_url', $settings['webhooks.webhook_user_delete_url'] ?? '') }}" placeholder="https://your-server.com/webhook/user-delete">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_fd698b') }}</label>
            <input type="url" name="webhook_user_update_url" class="form-input w-full" value="{{ old('webhook_user_update_url', $settings['webhooks.webhook_user_update_url'] ?? '') }}" placeholder="https://your-server.com/webhook/user-update">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_240504') }}</label>
            <input type="url" name="webhook_code_redeemed_url" class="form-input w-full" value="{{ old('webhook_code_redeemed_url', $settings['webhooks.webhook_code_redeemed_url'] ?? '') }}" placeholder="https://your-server.com/webhook/code-redeemed">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_132977') }}</label>
            <input type="url" name="webhook_contact_url" class="form-input w-full" value="{{ old('webhook_contact_url', $settings['webhooks.webhook_contact_url'] ?? '') }}" placeholder="https://your-server.com/webhook/contact">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_e2baee') }}</label>
            <input type="url" name="webhook_domain_new_url" class="form-input w-full" value="{{ old('webhook_domain_new_url', $settings['webhooks.webhook_domain_new_url'] ?? '') }}" placeholder="https://your-server.com/webhook/domain-new">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_6da02a') }}</label>
            <input type="url" name="webhook_domain_update_url" class="form-input w-full" value="{{ old('webhook_domain_update_url', $settings['webhooks.webhook_domain_update_url'] ?? '') }}" placeholder="https://your-server.com/webhook/domain-update">
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_de4163') }}</label>
            <input type="url" name="start_url" class="form-input w-full" value="{{ old('start_url', $settings['webhooks.start_url'] ?? '') }}" placeholder="https://your-server.com/webhook/cron-start">
            <p class="form-hint">{{ __('settings.webhooks.t_b8e317') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_a8a913') }}</label>
            <input type="url" name="end_url" class="form-input w-full" value="{{ old('end_url', $settings['webhooks.end_url'] ?? '') }}" placeholder="https://your-server.com/webhook/cron-end">
            <p class="form-hint">{{ __('settings.webhooks.t_f9c0af') }}</p>
        </div>
    </div>
</div>

{{-- 原版对标补充：事件开关与密钥（AltumCode webhooks） --}}
<section class="settings-section mt-6">
    <div class="settings-section-header">
        <div>
            <h3 class="settings-section-title">{{ __('settings.webhooks.t_0141c2') }}</h3>
            <p class="settings-section-desc">{{ __('settings.webhooks.t_5df3a9') }}</p>
        </div>
    </div>
    <div class="settings-section-body">
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_95040d') }}</label>
                        <input type="text" name="webhooks_secret_key" class="form-input font-mono" value="{{ old('webhooks_secret_key', $settings['webhooks.webhooks_secret_key'] ?? '') }}" placeholder="{{ __('settings.webhooks.signing_secret_placeholder') }}">
            <p class="form-hint">{{ __('settings.webhooks.t_b77c01') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.webhooks.t_446c28') }}</label>
            <textarea name="wait_for_response_domains" rows="3" class="form-input w-full font-mono text-[13px]">{{ old('wait_for_response_domains', $settings['webhooks.wait_for_response_domains'] ?? '') }}</textarea>
            <p class="form-hint">{{ __('settings.webhooks.t_084cc4') }}</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @php
                $whEvents = [
                    'webhooks_user_new' => [__('settings.webhooks.event_user_registered'), true],
                    'webhooks_user_update' => [__('settings.webhooks.event_user_updated'), false],
                    'webhooks_user_delete' => [__('settings.webhooks.event_user_deleted'), true],
                    'webhooks_payment_new' => [__('settings.webhooks.event_new_payment'), true],
                    'webhooks_code_redeemed' => [__('settings.webhooks.event_coupon_redeemed'), false],
                    'webhooks_contact' => [__('settings.webhooks.event_contact_submitted'), false],
                    'webhooks_cron_start' => [__('settings.webhooks.event_cron_started'), false],
                    'webhooks_cron_end' => [__('settings.webhooks.event_cron_finished'), false],
                    'webhooks_domain_new' => [__('settings.webhooks.event_domain_added'), false],
                    'webhooks_domain_update' => [__('settings.webhooks.event_domain_status_changed'), false],
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



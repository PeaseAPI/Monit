{{-- 支付设置（规格书 §11：货币、税费、发票、Stripe/PayPal/Razorpay/线下付款） --}}
<div class="space-y-4">
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.currency') }}</label>
        <input type="text" name="currency" value="{{ old('currency', $settings['payment.currency'] ?? 'USD') }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm" maxlength="3"></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="taxes_enabled" value="1" {{ old('taxes_enabled', ($settings['payment.taxes_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.taxes_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="auto_currency_detection" value="1" {{ old('auto_currency_detection', ($settings['payment.auto_currency_detection'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.auto_currency') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="invoice_is_enabled" value="1" {{ old('invoice_is_enabled', ($settings['payment.invoice_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">启用发票</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="user_plan_expiry_reminder" value="1" {{ old('user_plan_expiry_reminder', ($settings['payment.user_plan_expiry_reminder'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">套餐到期提醒</label></div>

    <h3 class="pt-4 text-base font-semibold">Stripe</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="stripe_is_enabled" value="1" {{ old('stripe_is_enabled', ($settings['payment.stripe_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <div><label class="block text-sm text-zinc-600">Publishable Key</label><input type="text" name="stripe_publishable_key" value="{{ old('stripe_publishable_key', $settings['payment.stripe_publishable_key'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></div>
    <div><label class="block text-sm text-zinc-600">Secret Key</label><input type="password" name="stripe_secret_key" value="{{ old('stripe_secret_key', $settings['payment.stripe_secret_key'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></div>

    <h3 class="pt-4 text-base font-semibold">PayPal</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="paypal_is_enabled" value="1" {{ old('paypal_is_enabled', ($settings['payment.paypal_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <div><label class="block text-sm text-zinc-600">Client ID</label><input type="text" name="paypal_client_id" value="{{ old('paypal_client_id', $settings['payment.paypal_client_id'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></div>
    <div><label class="block text-sm text-zinc-600">Secret</label><input type="password" name="paypal_secret" value="{{ old('paypal_secret', $settings['payment.paypal_secret'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></div>

    <h3 class="pt-4 text-base font-semibold">Razorpay</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="razorpay_is_enabled" value="1" {{ old('razorpay_is_enabled', ($settings['payment.razorpay_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <div><label class="block text-sm text-zinc-600">Key ID</label><input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id', $settings['payment.razorpay_key_id'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></div>

    <h3 class="pt-4 text-base font-semibold">WeChat Pay</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="wechat_pay_is_enabled" value="1" {{ old('wechat_pay_is_enabled', ($settings['payment.wechat_pay_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <p class="text-xs text-zinc-400">Native 扫码支付（API v2）。凭据经 .env 配置：WECHAT_PAY_APP_ID / WECHAT_PAY_MCH_ID / WECHAT_PAY_API_KEY</p>

    <h3 class="pt-4 text-base font-semibold">Alipay</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="alipay_is_enabled" value="1" {{ old('alipay_is_enabled', ($settings['payment.alipay_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <p class="text-xs text-zinc-400">电脑网站支付（RSA2）。凭据经 .env 配置：ALIPAY_APP_ID / ALIPAY_PRIVATE_KEY / ALIPAY_PUBLIC_KEY</p>

    <h3 class="pt-4 text-base font-semibold">Offline Payment</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="offline_is_enabled" value="1" {{ old('offline_is_enabled', ($settings['payment.offline_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <div><label class="block text-sm text-zinc-600">{{ __('admin.offline_instructions') }}</label><textarea name="offline_instructions" rows="4" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ old('offline_instructions', $settings['payment.offline_instructions'] ?? '') }}</textarea></div>
</div>

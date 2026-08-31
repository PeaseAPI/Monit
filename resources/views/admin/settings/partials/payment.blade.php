{{-- 支付设置（规格书 §11：货币、税费、发票、Stripe/PayPal/Razorpay/线下付款 + §10.4 多货币汇率） --}}
<div class="space-y-4">
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.default_currency') }}</label>
        <input type="text" name="currency" value="{{ old('currency', $settings['payment.currency'] ?? \App\Support\Currency::default()) }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm uppercase" maxlength="3">
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.default_currency_hint') }}</p></div>

    {{-- 多货币清单：任意增删货币 + 汇率（1 默认货币 = rate 该货币） --}}
    <div>
        <label class="block text-sm font-medium text-zinc-700">{{ __('admin.payment_currencies') }}</label>
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.payment_currencies_hint') }}</p>
        @php
            $storedCurrencies = old('currencies', null);
            if (! is_array($storedCurrencies)) {
                $raw = $settings['payment.currencies'] ?? '';
                $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
                $storedCurrencies = is_array($decoded) ? $decoded : [];
            }
            $currencyRows = collect($storedCurrencies)->map(fn ($row, $code) => ['code' => $code, 'name' => $row['name'] ?? '', 'symbol' => $row['symbol'] ?? '', 'rate' => $row['rate'] ?? ''])->values()->all();
            $currencyRows[] = ['code' => '', 'name' => '', 'symbol' => '', 'rate' => ''];
            $currencyRows[] = ['code' => '', 'name' => '', 'symbol' => '', 'rate' => ''];
        @endphp
        <div class="mt-2 space-y-2" id="currency-rows">
            @foreach($currencyRows as $row)
            <div class="grid grid-cols-12 gap-2 currency-row">
                <input type="text" name="currencies[{{ $row['code'] }}][code_display]" data-code-input value="{{ $row['code'] }}" placeholder="{{ __('admin.currency_code') }}" maxlength="3" class="col-span-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm uppercase" @if($row['code'] !== '')readonly title="{{ __('admin.currency_code_readonly') }}"@endif>
                <input type="text" name="currencies[{{ $row['code'] }}][name]" value="{{ $row['name'] }}" placeholder="{{ __('admin.currency_name') }}" class="col-span-4 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <input type="text" name="currencies[{{ $row['code'] }}][symbol]" value="{{ $row['symbol'] }}" placeholder="{{ __('admin.currency_symbol') }}" maxlength="8" class="col-span-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <input type="number" step="any" min="0.000001" name="currencies[{{ $row['code'] }}][rate]" value="{{ $row['rate'] }}" placeholder="{{ __('admin.currency_rate') }}" class="col-span-3 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                <button type="button" onclick="this.closest('.currency-row').remove()" class="col-span-1 rounded-lg border border-zinc-200 px-2 py-2 text-xs text-red-500 hover:bg-red-50">✕</button>
            </div>
            @endforeach
        </div>
        <button type="button" onclick="addCurrencyRow()" class="mt-2 rounded-lg border border-dashed border-zinc-300 px-4 py-2 text-xs text-zinc-500 hover:bg-zinc-50">+ {{ __('admin.add_currency') }}</button>
    </div>

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
    {{-- 原版对标补充：支付行为开关（AltumCode payment） --}}
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">支付行为（原版对标）</h3>
                <p class="settings-section-desc">支付模式、试用与税费账单开关</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用在线支付</span>
                    <span class="settings-field-row-hint">关闭后仅保留免费套餐（原版 payment_is_enabled）</span>
                </span>
                <input type="checkbox" name="payment_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.payment_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div class="settings-field-grid">
                <div>
                    <label class="form-label">默认支付模式</label>
                    <select name="default_payment_type" class="form-select">
                        @foreach (['recurring' => '订阅制', 'one_time' => '一次性'] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_payment_type', $settings['payment.default_payment_type'] ?? 'recurring') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">原版 default_payment_type</p>
                </div>
                <div>
                    <label class="form-label">默认周期</label>
                    <select name="default_payment_frequency" class="form-select">
                        @foreach (['monthly' => '月付', 'annual' => '年付', 'lifetime' => '买断'] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_payment_frequency', $settings['payment.default_payment_frequency'] ?? 'monthly') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">原版 default_payment_frequency</p>
                </div>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用优惠码</span>
                    <span class="settings-field-row-hint">允许结账时使用优惠码（原版 codes_is_enabled）</span>
                </span>
                <input type="checkbox" name="codes_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.codes_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">税费与账单地址</span>
                    <span class="settings-field-row-hint">结账时收集税号与账单地址（原版 taxes_and_billing）</span>
                </span>
                <input type="checkbox" name="taxes_and_billing_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.taxes_and_billing_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">试用需绑卡</span>
                    <span class="settings-field-row-hint">开启试用仍要求填写支付方式（原版 trial_require_card）</span>
                </span>
                <input type="checkbox" name="trial_require_card" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.trial_require_card'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">套餐到期检查器</span>
                    <span class="settings-field-row-hint">定时任务自动将过期用户降为免费套餐（原版）</span>
                </span>
                <input type="checkbox" name="user_plan_expiry_checker_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.user_plan_expiry_checker_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">汇率服务 API Key</label>
                <input type="text" name="currency_exchange_api_key" class="form-input" value="{{ old('currency_exchange_api_key', $settings['payment.currency_exchange_api_key'] ?? '') }}" placeholder="exchangerate.host / openexchangerates key">
                <p class="form-hint">用于自动刷新多货币汇率（原版 currency_exchange_api_key）</p>
            </div>
        </div>
    </section>
</div>

<script>
function addCurrencyRow() {
    const html = `<div class="grid grid-cols-12 gap-2 currency-row">
        <input type="text" data-code-input value="" placeholder="{{ __('admin.currency_code') }}" maxlength="3" class="col-span-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm uppercase">
        <input type="text" name="currencies[__NEW__][name]" value="" placeholder="{{ __('admin.currency_name') }}" class="col-span-4 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <input type="text" name="currencies[__NEW__][symbol]" value="" placeholder="{{ __('admin.currency_symbol') }}" maxlength="8" class="col-span-2 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <input type="number" step="any" min="0.000001" name="currencies[__NEW__][rate]" value="" placeholder="{{ __('admin.currency_rate') }}" class="col-span-3 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
        <button type="button" onclick="this.closest('.currency-row').remove()" class="col-span-1 rounded-lg border border-zinc-200 px-2 py-2 text-xs text-red-500 hover:bg-red-50">✕</button>
    </div>`;
    document.getElementById('currency-rows').insertAdjacentHTML('beforeend', html);
    bindCurrencyCodeInput(document.querySelector('#currency-rows .currency-row:last-child [data-code-input]'));
}
function bindCurrencyCodeInput(input) {
    input.addEventListener('change', function () {
        const code = this.value.trim().toUpperCase();
        if (!/^[A-Z]{3}$/.test(code)) { return; }
        this.closest('.currency-row').querySelectorAll('input[name]').forEach(el => {
            el.name = el.name.replace(/currencies\[[^\]]*\]/, 'currencies[' + code + ']');
        });
    });
}
document.querySelectorAll('#currency-rows [data-code-input]').forEach(bindCurrencyCodeInput);
</script>

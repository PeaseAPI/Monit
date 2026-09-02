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
                <input type="text" name="currencies[{{ $row['code'] }}][code_display]" data-code-input value="{{ $row['code'] }}" placeholder="{{ __('admin.currency_code') }}" maxlength="3" class="form-input col-span-2 uppercase" @if($row['code'] !== '')readonly title="{{ __('admin.currency_code_readonly') }}"@endif>
                <input type="text" name="currencies[{{ $row['code'] }}][name]" value="{{ $row['name'] }}" placeholder="{{ __('admin.currency_name') }}" class="form-input col-span-4">
                <input type="text" name="currencies[{{ $row['code'] }}][symbol]" value="{{ $row['symbol'] }}" placeholder="{{ __('admin.currency_symbol') }}" maxlength="8" class="form-input col-span-2">
                <input type="number" step="any" min="0.000001" name="currencies[{{ $row['code'] }}][rate]" value="{{ $row['rate'] }}" placeholder="{{ __('admin.currency_rate') }}" class="form-input col-span-3">
                <button type="button" onclick="this.closest('.currency-row').remove()" class="col-span-1 rounded-lg border border-zinc-200 px-2 py-2 text-xs text-red-500 hover:bg-red-50">✕</button>
            </div>
            @endforeach
        </div>
        <button type="button" onclick="addCurrencyRow()" class="mt-2 rounded-lg border border-dashed border-zinc-300 px-4 py-2 text-xs text-zinc-500 hover:bg-zinc-50">+ {{ __('admin.add_currency') }}</button>
    </div>

    <div class="flex items-center gap-3"><input type="checkbox" name="taxes_enabled" value="1" {{ old('taxes_enabled', ($settings['payment.taxes_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.taxes_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="auto_currency_detection" value="1" {{ old('auto_currency_detection', ($settings['payment.auto_currency_detection'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.auto_currency') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="invoice_is_enabled" value="1" {{ old('invoice_is_enabled', ($settings['payment.invoice_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('settings.payment.t_75ddc7') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="user_plan_expiry_reminder" value="1" {{ old('user_plan_expiry_reminder', ($settings['payment.user_plan_expiry_reminder'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('settings.payment.t_867e06') }}</label></div>

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
    <p class="text-xs text-zinc-400">{{ __('settings.payment.t_21c2f2') }}</p>

    <h3 class="pt-4 text-base font-semibold">Alipay</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="alipay_is_enabled" value="1" {{ old('alipay_is_enabled', ($settings['payment.alipay_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <p class="text-xs text-zinc-400">{{ __('settings.payment.t_9983be') }}</p>

    <h3 class="pt-4 text-base font-semibold">Offline Payment</h3>
    <div class="flex items-center gap-3"><input type="checkbox" name="offline_is_enabled" value="1" {{ old('offline_is_enabled', ($settings['payment.offline_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.enabled') }}</label></div>
    <div><label class="block text-sm text-zinc-600">{{ __('admin.offline_instructions') }}</label><textarea name="offline_instructions" rows="4" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ old('offline_instructions', $settings['payment.offline_instructions'] ?? '') }}</textarea></div>
    {{-- 原版对标补充：支付行为开关（AltumCode payment） --}}
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.payment.t_2d78a7') }}</h3>
                <p class="settings-section-desc">{{ __('settings.payment.t_a6d8a1') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.payment.t_74d2bb') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.payment.t_ab8316') }}</span>
                </span>
                <input type="checkbox" name="payment_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.payment_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div class="settings-field-grid">
                <div>
                    <label class="form-label">{{ __('settings.payment.t_d101d5') }}</label>
                    <select name="default_payment_type" class="form-select">
                        @foreach (['recurring' => __('settings.payment.t_3fdc8b'), 'one_time' => __('settings.payment.t_ebc149')] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_payment_type', $settings['payment.default_payment_type'] ?? 'recurring') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">{{ __('settings.payment.t_aa5bf9') }}</p>
                </div>
                <div>
                    <label class="form-label">{{ __('settings.payment.t_16891b') }}</label>
                    <select name="default_payment_frequency" class="form-select">
                        @foreach (['monthly' => __('settings.payment.t_e45ac6'), 'annual' => __('settings.payment.t_30806d'), 'lifetime' => __('settings.payment.t_162a73')] as $v => $l)
                            <option value="{{ $v }}" {{ old('default_payment_frequency', $settings['payment.default_payment_frequency'] ?? 'monthly') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                    <p class="form-hint">{{ __('settings.payment.t_325952') }}</p>
                </div>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.payment.t_f39cc5') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.payment.t_5b626e') }}</span>
                </span>
                <input type="checkbox" name="codes_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.codes_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.payment.t_ba7696') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.payment.t_15a6d1') }}</span>
                </span>
                <input type="checkbox" name="taxes_and_billing_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.taxes_and_billing_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.payment.t_f42e31') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.payment.t_ebb35b') }}</span>
                </span>
                <input type="checkbox" name="trial_require_card" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.trial_require_card'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.payment.t_f0311d') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.payment.t_e95c25') }}</span>
                </span>
                <input type="checkbox" name="user_plan_expiry_checker_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['payment.user_plan_expiry_checker_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.payment.t_062be1') }}</label>
                <input type="text" name="currency_exchange_api_key" class="form-input" value="{{ old('currency_exchange_api_key', $settings['payment.currency_exchange_api_key'] ?? '') }}" placeholder="exchangerate.host / openexchangerates key">
                <p class="form-hint">{{ __('settings.payment.t_a9306f') }}</p>
            </div>
        </div>
    </section>
</div>

<script>
function addCurrencyRow() {
    const html = `<div class="grid grid-cols-12 gap-2 currency-row">
        <input type="text" data-code-input value="" placeholder="{{ __('admin.currency_code') }}" maxlength="3" class="form-input col-span-2 uppercase">
        <input type="text" name="currencies[__NEW__][name]" value="" placeholder="{{ __('admin.currency_name') }}" class="form-input col-span-4">
        <input type="text" name="currencies[__NEW__][symbol]" value="" placeholder="{{ __('admin.currency_symbol') }}" maxlength="8" class="form-input col-span-2">
        <input type="number" step="any" min="0.000001" name="currencies[__NEW__][rate]" value="" placeholder="{{ __('admin.currency_rate') }}" class="form-input col-span-3">
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

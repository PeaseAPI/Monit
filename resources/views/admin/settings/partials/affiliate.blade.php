<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">{{ __('settings.affiliate.title') }}</h3>
                <p class="settings-section-desc">{{ __('settings.affiliate.desc') }}</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">{{ __('settings.affiliate.enable') }}</span>
                    <span class="settings-field-row-hint">{{ __('settings.affiliate.enable_hint') }}</span>
                </span>
                <input type="checkbox" name="affiliate_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['affiliate.affiliate_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">{{ __('settings.affiliate.commission_type') }}</label>
                <select name="affiliate_commission_type" class="form-select">
                    @foreach (['percentage' => __('settings.affiliate.type_percentage'), 'fixed' => __('settings.affiliate.type_fixed')] as $v => $l)
                        <option value="{{ $v }}" {{ old('affiliate_commission_type', $settings['affiliate.affiliate_commission_type'] ?? 'percentage') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.affiliate.commission_type_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.commission_rate') }}</label>
                <input type="number" name="affiliate_commission_percentage" class="form-input" value="{{ old('affiliate_commission_percentage', $settings['affiliate.affiliate_commission_percentage'] ?? '10') }}" placeholder="10">
                <p class="form-hint">{{ __('settings.affiliate.commission_rate_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.tracking_type') }}</label>
                <select name="affiliate_tracking_type" class="form-select">
                    @foreach (['cookie' => __('settings.affiliate.tracking_cookie'), 'code' => __('settings.affiliate.tracking_code')] as $v => $l)
                        <option value="{{ $v }}" {{ old('affiliate_tracking_type', $settings['affiliate.affiliate_tracking_type'] ?? 'cookie') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">{{ __('settings.affiliate.tracking_type_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.tracking_duration') }}</label>
                <input type="number" name="affiliate_tracking_duration" class="form-input" value="{{ old('affiliate_tracking_duration', $settings['affiliate.affiliate_tracking_duration'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.affiliate.tracking_duration_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.cookie_duration') }}</label>
                <input type="number" name="affiliate_cookie_duration_days" class="form-input" value="{{ old('affiliate_cookie_duration_days', $settings['affiliate.affiliate_cookie_duration_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">{{ __('settings.affiliate.cookie_duration_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.min_withdrawal') }}</label>
                <input type="number" name="affiliate_minimum_withdrawal_amount" class="form-input" value="{{ old('affiliate_minimum_withdrawal_amount', $settings['affiliate.affiliate_minimum_withdrawal_amount'] ?? '100') }}" placeholder="100">
                <p class="form-hint">{{ __('settings.affiliate.min_withdrawal_hint') }}</p>
            </div>
            <div>
                <label class="form-label">{{ __('settings.affiliate.withdrawal_notes') }}</label>
                <textarea name="affiliate_withdrawal_notes" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('affiliate_withdrawal_notes', $settings['affiliate.affiliate_withdrawal_notes'] ?? '') }}</textarea>
                <p class="form-hint">{{ __('settings.affiliate.withdrawal_notes_hint') }}</p>
            </div>
        </div>
    </section>
</div>

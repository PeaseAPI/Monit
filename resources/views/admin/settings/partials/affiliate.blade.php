<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">推广联盟</h3>
                <p class="settings-section-desc">推荐返佣计划（原版 affiliate）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用推广返佣</span>
                    <span class="settings-field-row-hint">用户可通过推荐链接获得佣金</span>
                </span>
                <input type="checkbox" name="affiliate_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['affiliate.affiliate_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">佣金类型</label>
                <select name="affiliate_commission_type" class="form-select">
                    @foreach (['percentage' => '百分比（%）', 'fixed' => '固定金额'] as $v => $l)
                        <option value="{{ $v }}" {{ old('affiliate_commission_type', $settings['affiliate.affiliate_commission_type'] ?? 'percentage') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">百分比或固定金额（原版）</p>
            </div>
            <div>
                <label class="form-label">佣金比例</label>
                <input type="number" name="affiliate_commission_percentage" class="form-input" value="{{ old('affiliate_commission_percentage', $settings['affiliate.affiliate_commission_percentage'] ?? '10') }}" placeholder="10">
                <p class="form-hint">百分比模式下的佣金比例</p>
            </div>
            <div>
                <label class="form-label">归因方式</label>
                <select name="affiliate_tracking_type" class="form-select">
                    @foreach (['cookie' => 'Cookie 归因', 'code' => '推荐码归因'] as $v => $l)
                        <option value="{{ $v }}" {{ old('affiliate_tracking_type', $settings['affiliate.affiliate_tracking_type'] ?? 'cookie') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">Cookie 或专属推荐码（原版）</p>
            </div>
            <div>
                <label class="form-label">归因有效期（天）</label>
                <input type="number" name="affiliate_tracking_duration" class="form-input" value="{{ old('affiliate_tracking_duration', $settings['affiliate.affiliate_tracking_duration'] ?? '30') }}" placeholder="30">
                <p class="form-hint">点击后多少天内成交有效（原版）</p>
            </div>
            <div>
                <label class="form-label">Cookie 有效期（天）</label>
                <input type="number" name="affiliate_cookie_duration_days" class="form-input" value="{{ old('affiliate_cookie_duration_days', $settings['affiliate.affiliate_cookie_duration_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">推荐 Cookie 的保留天数</p>
            </div>
            <div>
                <label class="form-label">最低提现金额</label>
                <input type="number" name="affiliate_minimum_withdrawal_amount" class="form-input" value="{{ old('affiliate_minimum_withdrawal_amount', $settings['affiliate.affiliate_minimum_withdrawal_amount'] ?? '100') }}" placeholder="100">
                <p class="form-hint">满多少元可申请提现</p>
            </div>
            <div>
                <label class="form-label">提现说明</label>
                <textarea name="affiliate_withdrawal_notes" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('affiliate_withdrawal_notes', $settings['affiliate.affiliate_withdrawal_notes'] ?? '') }}</textarea>
                <p class="form-hint">展示给用户的提现规则说明（原版）</p>
            </div>
        </div>
    </section>
</div>

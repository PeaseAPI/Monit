<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置联盟营销插件设置（规格书 §14.3：Affiliate 插件）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="affiliate_is_enabled" value="1" {{ ($settings['affiliate.affiliate_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用联盟营销
        </label>
        <div>
            <label class="form-label">佣金比例 (%)</label>
            <input type="number" name="affiliate_commission_percentage" class="form-input w-32" min="0" max="100" value="{{ old('affiliate_commission_percentage', $settings['affiliate.affiliate_commission_percentage'] ?? 10) }}">
        </div>
        <div>
            <label class="form-label">Cookie 追踪天数</label>
            <input type="number" name="affiliate_cookie_duration_days" class="form-input w-32" min="1" max="365" value="{{ old('affiliate_cookie_duration_days', $settings['affiliate.affiliate_cookie_duration_days'] ?? 30) }}">
        </div>
        <div>
            <label class="form-label">最低提现金额</label>
            <input type="number" name="affiliate_minimum_withdrawal_amount" class="form-input w-32" min="0" step="0.01" value="{{ old('affiliate_minimum_withdrawal_amount', $settings['affiliate.affiliate_minimum_withdrawal_amount'] ?? 50) }}">
        </div>
    </div>
</div>


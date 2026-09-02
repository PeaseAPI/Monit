{{-- 发票信息（business）：发票/信用票据抬头企业信息，原库 settings.business 组 16 字段 --}}
<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.business.desc') }}</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">{{ __('settings.business.brand_name') }}</label>
            <input type="text" name="brand_name" class="form-input w-full" value="{{ old('brand_name', $settings['business.brand_name'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.invoice_nr_prefix') }}</label>
            <input type="text" name="invoice_nr_prefix" class="form-input w-full" value="{{ old('invoice_nr_prefix', $settings['business.invoice_nr_prefix'] ?? 'INV-') }}" placeholder="INV-">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.company_name') }}</label>
            <input type="text" name="name" class="form-input w-full" value="{{ old('name', $settings['business.name'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.email') }}</label>
            <input type="email" name="email" class="form-input w-full" value="{{ old('email', $settings['business.email'] ?? '') }}">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">{{ __('settings.business.address') }}</label>
            <input type="text" name="address" class="form-input w-full" value="{{ old('address', $settings['business.address'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.city') }}</label>
            <input type="text" name="city" class="form-input w-full" value="{{ old('city', $settings['business.city'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.county') }}</label>
            <input type="text" name="county" class="form-input w-full" value="{{ old('county', $settings['business.county'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.zip') }}</label>
            <input type="text" name="zip" class="form-input w-full" value="{{ old('zip', $settings['business.zip'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.country_code') }}</label>
            <input type="text" name="country" class="form-input w-full" value="{{ old('country', $settings['business.country'] ?? '') }}" placeholder="CN">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.phone') }}</label>
            <input type="text" name="phone" class="form-input w-full" value="{{ old('phone', $settings['business.phone'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.tax_type') }}</label>
            <select name="tax_type" class="form-select">
                <option value="" {{ ($settings['business.tax_type'] ?? '') === '' ? 'selected' : '' }}>{{ __('settings.business.tax_type_none') }}</option>
                <option value="VAT" {{ ($settings['business.tax_type'] ?? '') === 'VAT' ? 'selected' : '' }}>VAT</option>
                <option value="GST" {{ ($settings['business.tax_type'] ?? '') === 'GST' ? 'selected' : '' }}>GST</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('settings.business.tax_id') }}</label>
            <input type="text" name="tax_id" class="form-input w-full" value="{{ old('tax_id', $settings['business.tax_id'] ?? '') }}">
        </div>
    </div>

    <div class="space-y-4 rounded-xl border border-zinc-200 p-4">
        <p class="text-sm font-medium text-zinc-700">{{ __('settings.business.custom_fields_title') }}</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">{{ __('settings.business.field_one_name') }}</label>
                <input type="text" name="custom_key_one" class="form-input w-full" value="{{ old('custom_key_one', $settings['business.custom_key_one'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">{{ __('settings.business.field_one_value') }}</label>
                <input type="text" name="custom_value_one" class="form-input w-full" value="{{ old('custom_value_one', $settings['business.custom_value_one'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">{{ __('settings.business.field_two_name') }}</label>
                <input type="text" name="custom_key_two" class="form-input w-full" value="{{ old('custom_key_two', $settings['business.custom_key_two'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">{{ __('settings.business.field_two_value') }}</label>
                <input type="text" name="custom_value_two" class="form-input w-full" value="{{ old('custom_value_two', $settings['business.custom_value_two'] ?? '') }}">
            </div>
        </div>
    </div>
</div>

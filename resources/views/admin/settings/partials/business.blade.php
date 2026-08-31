{{-- 发票信息（business）：发票/信用票据抬头企业信息，原库 settings.business 组 16 字段 --}}
<div class="space-y-6">
    <p class="text-sm text-zinc-500">发票与信用票据的开票方信息（保存后立即用于发票抬头与票号前缀）。</p>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">品牌名称（发票抬头）</label>
            <input type="text" name="brand_name" class="form-input w-full" value="{{ old('brand_name', $settings['business.brand_name'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">票号前缀</label>
            <input type="text" name="invoice_nr_prefix" class="form-input w-full" value="{{ old('invoice_nr_prefix', $settings['business.invoice_nr_prefix'] ?? 'INV-') }}" placeholder="INV-">
        </div>
        <div>
            <label class="form-label">公司/法人名称</label>
            <input type="text" name="name" class="form-input w-full" value="{{ old('name', $settings['business.name'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">电子邮箱</label>
            <input type="email" name="email" class="form-input w-full" value="{{ old('email', $settings['business.email'] ?? '') }}">
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">地址</label>
            <input type="text" name="address" class="form-input w-full" value="{{ old('address', $settings['business.address'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">城市</label>
            <input type="text" name="city" class="form-input w-full" value="{{ old('city', $settings['business.city'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">区/县</label>
            <input type="text" name="county" class="form-input w-full" value="{{ old('county', $settings['business.county'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">邮编</label>
            <input type="text" name="zip" class="form-input w-full" value="{{ old('zip', $settings['business.zip'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">国家/地区代码</label>
            <input type="text" name="country" class="form-input w-full" value="{{ old('country', $settings['business.country'] ?? '') }}" placeholder="CN">
        </div>
        <div>
            <label class="form-label">电话</label>
            <input type="text" name="phone" class="form-input w-full" value="{{ old('phone', $settings['business.phone'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">税号类型</label>
            <select name="tax_type" class="form-select">
                <option value="" {{ ($settings['business.tax_type'] ?? '') === '' ? 'selected' : '' }}>无</option>
                <option value="VAT" {{ ($settings['business.tax_type'] ?? '') === 'VAT' ? 'selected' : '' }}>VAT</option>
                <option value="GST" {{ ($settings['business.tax_type'] ?? '') === 'GST' ? 'selected' : '' }}>GST</option>
            </select>
        </div>
        <div>
            <label class="form-label">税号</label>
            <input type="text" name="tax_id" class="form-input w-full" value="{{ old('tax_id', $settings['business.tax_id'] ?? '') }}">
        </div>
    </div>

    <div class="space-y-4 rounded-xl border border-zinc-200 p-4">
        <p class="text-sm font-medium text-zinc-700">自定义发票字段（如银行账号、开户行）</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">字段一名称</label>
                <input type="text" name="custom_key_one" class="form-input w-full" value="{{ old('custom_key_one', $settings['business.custom_key_one'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">字段一内容</label>
                <input type="text" name="custom_value_one" class="form-input w-full" value="{{ old('custom_value_one', $settings['business.custom_value_one'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">字段二名称</label>
                <input type="text" name="custom_key_two" class="form-input w-full" value="{{ old('custom_key_two', $settings['business.custom_key_two'] ?? '') }}">
            </div>
            <div>
                <label class="form-label">字段二内容</label>
                <input type="text" name="custom_value_two" class="form-input w-full" value="{{ old('custom_value_two', $settings['business.custom_value_two'] ?? '') }}">
            </div>
        </div>
    </div>
</div>

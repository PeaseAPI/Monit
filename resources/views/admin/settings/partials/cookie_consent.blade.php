<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">基础</h3>
                <p class="settings-section-desc">Cookie 同意横幅开关与文案</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用 Cookie 同意</span>
                    <span class="settings-field-row-hint">在访客首次访问时显示同意横幅</span>
                </span>
                <input type="checkbox" name="cookie_consent_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">横幅类型</label>
                <select name="cookie_consent_type" class="form-select">
                    @foreach (['simple' => '简单提示', 'detailed' => '分类可选'] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_type', $settings['cookie_consent.cookie_consent_type'] ?? 'simple') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">简单提示或分类可选</p>
            </div>
            <div>
                <label class="form-label">标题</label>
                <input type="text" name="cookie_consent_title" class="form-input" value="{{ old('cookie_consent_title', $settings['cookie_consent.cookie_consent_title'] ?? '我们使用 Cookie') }}">
                <p class="form-hint">横幅主标题</p>
            </div>
            <div>
                <label class="form-label">说明文字</label>
                <textarea name="cookie_consent_description" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('cookie_consent_description', $settings['cookie_consent.cookie_consent_description'] ?? '') }}</textarea>
                <p class="form-hint">横幅正文说明</p>
            </div>
            <div>
                <label class="form-label">按钮文字</label>
                <input type="text" name="cookie_consent_button_text" class="form-input" value="{{ old('cookie_consent_button_text', $settings['cookie_consent.cookie_consent_button_text'] ?? '同意') }}">
                <p class="form-hint">同意按钮的文字</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">分类开关</h3>
                <p class="settings-section-desc">Cookie 分类的默认勾选状态（原版）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">必要 Cookie</span>
                    <span class="settings-field-row-hint">站点运行必需，始终启用</span>
                </span>
                <input type="checkbox" name="cookie_consent_necessary_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_necessary_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">分析 Cookie</span>
                    <span class="settings-field-row-hint">默认勾选统计类 Cookie</span>
                </span>
                <input type="checkbox" name="cookie_consent_analytics_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_analytics_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">营销 Cookie</span>
                    <span class="settings-field-row-hint">默认勾选营销类 Cookie</span>
                </span>
                <input type="checkbox" name="cookie_consent_targeting_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_targeting_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">展示与位置</h3>
                <p class="settings-section-desc">横幅的布局方式（原版）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">布局</label>
                <select name="cookie_consent_layout" class="form-select">
                    @foreach (['bar' => '底部横条', 'box' => '浮动卡片'] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_layout', $settings['cookie_consent.cookie_consent_layout'] ?? 'bar') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">横条或对话框</p>
            </div>
            <div>
                <label class="form-label">垂直位置</label>
                <select name="cookie_consent_position_y" class="form-select">
                    @foreach (['top' => '顶部', 'bottom' => '底部'] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_position_y', $settings['cookie_consent.cookie_consent_position_y'] ?? 'bottom') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">顶部或底部</p>
            </div>
            <div>
                <label class="form-label">水平位置</label>
                <select name="cookie_consent_position_x" class="form-select">
                    @foreach (['left' => '靠左', 'center' => '居中', 'right' => '靠右'] as $v => $l)
                        <option value="{{ $v }}" {{ old('cookie_consent_position_x', $settings['cookie_consent.cookie_consent_position_x'] ?? 'center') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">横条模式下的对齐</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">记录同意日志</span>
                    <span class="settings-field-row-hint">保存访客的同意记录备查</span>
                </span>
                <input type="checkbox" name="cookie_consent_logging_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['cookie_consent.cookie_consent_logging_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

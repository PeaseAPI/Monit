<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">公告条</h3>
                <p class="settings-section-desc">页面顶部滚动公告（原版 announcements）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用公告</span>
                    <span class="settings-field-row-hint">在页面顶部显示公告条</span>
                </span>
                <input type="checkbox" name="announcements_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">展示对象</label>
                <select name="announcements_type" class="form-select">
                    @foreach (['all' => '所有人', 'guests' => '仅访客', 'users' => '仅登录用户'] as $v => $l)
                        <option value="{{ $v }}" {{ old('announcements_type', $settings['announcements.announcements_type'] ?? 'all') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">公告面向的用户群</p>
            </div>
            <div>
                <label class="form-label">公告内容</label>
                <textarea name="announcements_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_content', $settings['announcements.announcements_content'] ?? '') }}</textarea>
                <p class="form-hint">支持简单 HTML</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">访客公告（原版 guests）</h3>
                <p class="settings-section-desc">面向未登录访客的独立公告</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用访客公告</span>
                    <span class="settings-field-row-hint">覆盖上方公告，仅访客可见</span>
                </span>
                <input type="checkbox" name="announcements_guests_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_guests_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">访客公告内容</label>
                <textarea name="announcements_guests_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_guests_content', $settings['announcements.announcements_guests_content'] ?? '') }}</textarea>
                <p class="form-hint">支持简单 HTML</p>
            </div>
            <div>
                <label class="form-label">文字颜色</label>
                <input type="text" name="announcements_guests_text_color" class="form-input" value="{{ old('announcements_guests_text_color', $settings['announcements.announcements_guests_text_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">十六进制色值，如 #ffffff</p>
            </div>
            <div>
                <label class="form-label">背景颜色</label>
                <input type="text" name="announcements_guests_background_color" class="form-input" value="{{ old('announcements_guests_background_color', $settings['announcements.announcements_guests_background_color'] ?? '#6366f1') }}" placeholder="#6366f1">
                <p class="form-hint">十六进制色值，如 #6366f1</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">用户公告（原版 users）</h3>
                <p class="settings-section-desc">面向登录用户的独立公告</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用用户公告</span>
                    <span class="settings-field-row-hint">覆盖上方公告，仅登录用户可见</span>
                </span>
                <input type="checkbox" name="announcements_users_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['announcements.announcements_users_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">用户公告内容</label>
                <textarea name="announcements_users_content" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('announcements_users_content', $settings['announcements.announcements_users_content'] ?? '') }}</textarea>
                <p class="form-hint">支持简单 HTML</p>
            </div>
            <div>
                <label class="form-label">文字颜色</label>
                <input type="text" name="announcements_users_text_color" class="form-input" value="{{ old('announcements_users_text_color', $settings['announcements.announcements_users_text_color'] ?? '#ffffff') }}" placeholder="#ffffff">
                <p class="form-hint">十六进制色值</p>
            </div>
            <div>
                <label class="form-label">背景颜色</label>
                <input type="text" name="announcements_users_background_color" class="form-input" value="{{ old('announcements_users_background_color', $settings['announcements.announcements_users_background_color'] ?? '#0ea5e9') }}" placeholder="#0ea5e9">
                <p class="form-hint">十六进制色值</p>
            </div>
        </div>
    </section>
</div>

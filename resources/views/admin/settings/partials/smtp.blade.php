<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">连接参数</h3>
                <p class="settings-section-desc">SMTP 服务器与端口（保存于 settings，发送时优先读取 .env MAIL_*）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">SMTP 主机</label>
                <input type="text" name="smtp_host" class="form-input" value="{{ old('smtp_host', $settings['smtp.smtp_host'] ?? '') }}">
                <p class="form-hint">如 smtp.exmail.qq.com</p>
            </div>
            <div>
                <label class="form-label">端口</label>
                <input type="number" name="smtp_port" class="form-input" value="{{ old('smtp_port', $settings['smtp.smtp_port'] ?? '465') }}" placeholder="465">
                <p class="form-hint">常见 25 / 465 / 587</p>
            </div>
            <div>
                <label class="form-label">加密方式</label>
                <select name="smtp_encryption" class="form-select">
                    @foreach (['ssl' => 'SSL（465）', 'tls' => 'TLS（587）', 'none' => '无加密（25）'] as $v => $l)
                        <option value="{{ $v }}" {{ old('smtp_encryption', $settings['smtp.smtp_encryption'] ?? 'ssl') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
                <p class="form-hint">SSL/TLS 或不加密</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用认证</span>
                    <span class="settings-field-row-hint">大多数服务商需要开启</span>
                </span>
                <input type="checkbox" name="smtp_auth" value="1" class="input-toggle"
                    {{ filter_var($settings['smtp.smtp_auth'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">用户名</label>
                <input type="text" name="smtp_username" class="form-input" value="{{ old('smtp_username', $settings['smtp.smtp_username'] ?? '') }}">
                <p class="form-hint">通常为发件邮箱</p>
            </div>
            <div>
                <label class="form-label">密码 / 授权码</label>
                <input type="password" name="smtp_password" autocomplete="new-password" class="form-input" value="{{ old('smtp_password', $settings['smtp.smtp_password'] ?? '') }}">
                <p class="form-hint">SMTP 登录凭据</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">发件人信息</h3>
                <p class="settings-section-desc">邮件头部的发件与回复地址（原版）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">发件人名称</label>
                <input type="text" name="smtp_from_name" class="form-input" value="{{ old('smtp_from_name', $settings['smtp.smtp_from_name'] ?? '') }}">
                <p class="form-hint">显示在发件人一栏</p>
            </div>
            <div>
                <label class="form-label">发件邮箱</label>
                <input type="text" name="smtp_from_email" class="form-input" value="{{ old('smtp_from_email', $settings['smtp.smtp_from_email'] ?? '') }}">
                <p class="form-hint">发件人地址</p>
            </div>
            <div>
                <label class="form-label">回复人名称</label>
                <input type="text" name="smtp_reply_to_name" class="form-input" value="{{ old('smtp_reply_to_name', $settings['smtp.smtp_reply_to_name'] ?? '') }}">
                <p class="form-hint">回复时显示的名称（原版）</p>
            </div>
            <div>
                <label class="form-label">回复邮箱</label>
                <input type="text" name="smtp_reply_to" class="form-input" value="{{ old('smtp_reply_to', $settings['smtp.smtp_reply_to'] ?? '') }}">
                <p class="form-hint">回复邮件投递地址（原版）</p>
            </div>
            <div>
                <label class="form-label">抄送</label>
                <input type="text" name="smtp_cc" class="form-input" value="{{ old('smtp_cc', $settings['smtp.smtp_cc'] ?? '') }}">
                <p class="form-hint">多个地址逗号分隔（原版）</p>
            </div>
            <div>
                <label class="form-label">密送</label>
                <input type="text" name="smtp_bcc" class="form-input" value="{{ old('smtp_bcc', $settings['smtp.smtp_bcc'] ?? '') }}">
                <p class="form-hint">多个地址逗号分隔（原版）</p>
            </div>
        </div>
    </section>
</div>

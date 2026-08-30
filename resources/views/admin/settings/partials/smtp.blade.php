<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置 SMTP 邮件发送服务</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">SMTP 主机</label>
            <input type="text" name="smtp_host" class="form-input" value="{{ old('smtp_host', $settings['smtp.smtp_host'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">SMTP 端口</label>
            <input type="number" name="smtp_port" class="form-input" value="{{ old('smtp_port', $settings['smtp.smtp_port'] ?? '587') }}">
        </div>
        <div>
            <label class="form-label">SMTP 用户名</label>
            <input type="text" name="smtp_username" class="form-input" value="{{ old('smtp_username', $settings['smtp.smtp_username'] ?? '') }}">
        </div>
        <div>
            <label class="form-label">SMTP 密码</label>
            <input type="password" name="smtp_password" class="form-input" placeholder="留空则不修改">
        </div>
        <div>
            <label class="form-label">加密方式</label>
            <select name="smtp_encryption" class="form-select">
                <option value="tls" {{ ($settings['smtp.smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ ($settings['smtp.smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                <option value="none" {{ ($settings['smtp.smtp_encryption'] ?? 'tls') === 'none' ? 'selected' : '' }}>无</option>
            </select>
        </div>
        <div>
            <label class="form-label">发件人名称</label>
            <input type="text" name="smtp_from_name" class="form-input" value="{{ old('smtp_from_name', $settings['smtp.smtp_from_name'] ?? config('app.name')) }}">
        </div>
        <div>
            <label class="form-label">发件人邮箱</label>
            <input type="email" name="smtp_from_email" class="form-input" value="{{ old('smtp_from_email', $settings['smtp.smtp_from_email'] ?? '') }}">
        </div>
    </div>
</div>


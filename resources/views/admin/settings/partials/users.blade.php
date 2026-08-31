<div class="space-y-6">
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">注册与激活</h3>
                <p class="settings-section-desc">新用户注册流程</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">开放注册</span>
                    <span class="settings-field-row-hint">关闭后注册入口不可用</span>
                </span>
                <input type="checkbox" name="register_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">邮箱激活</span>
                    <span class="settings-field-row-hint">注册后需点击邮件链接激活（原版 email_confirmation）</span>
                </span>
                <input type="checkbox" name="email_activation_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.email_activation_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">发送欢迎邮件</span>
                    <span class="settings-field-row-hint">注册成功后发送欢迎邮件</span>
                </span>
                <input type="checkbox" name="welcome_email_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.welcome_email_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">注册需同意条款</span>
                    <span class="settings-field-row-hint">注册时强制勾选服务条款</span>
                </span>
                <input type="checkbox" name="user_registration_require_consent" value="1" class="input-toggle"
                    {{ filter_var($settings['users.user_registration_require_consent'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">注册页订阅勾选框</span>
                    <span class="settings-field-row-hint">注册表单显示邮件订阅勾选框</span>
                </span>
                <input type="checkbox" name="register_display_newsletter_checkbox" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_display_newsletter_checkbox'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">账户页订阅开关</span>
                    <span class="settings-field-row-hint">账户设置中显示订阅开关</span>
                </span>
                <input type="checkbox" name="account_display_newsletter_checkbox" value="1" class="input-toggle"
                    {{ filter_var($settings['users.account_display_newsletter_checkbox'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">账号安全</h3>
                <p class="settings-section-desc">登录与会话策略</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">两步验证</span>
                    <span class="settings-field-row-hint">允许用户启用 TOTP 两步验证</span>
                </span>
                <input type="checkbox" name="two_fa_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.two_fa_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">记住我默认勾选</span>
                    <span class="settings-field-row-hint">登录页「记住我」复选框默认选中</span>
                </span>
                <input type="checkbox" name="login_rememberme_checkbox_is_checked" value="1" class="input-toggle"
                    {{ filter_var($settings['users.login_rememberme_checkbox_is_checked'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">记住我有效天数</label>
                <input type="number" name="login_rememberme_cookie_days" class="form-input" value="{{ old('login_rememberme_cookie_days', $settings['users.login_rememberme_cookie_days'] ?? '30') }}" placeholder="30">
                <p class="form-hint">记住登录状态的天数（1-365）</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">自动清理</h3>
                <p class="settings-section-desc">闲置与未激活账号处理</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">清理未激活用户</span>
                    <span class="settings-field-row-hint">超期未激活的账号自动删除</span>
                </span>
                <input type="checkbox" name="auto_delete_unconfirmed_users" value="1" class="input-toggle"
                    {{ filter_var($settings['users.auto_delete_unconfirmed_users'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">清理期限（天）</label>
                <input type="number" name="auto_delete_unconfirmed_users_days" class="form-input" value="{{ old('auto_delete_unconfirmed_users_days', $settings['users.auto_delete_unconfirmed_users_days'] ?? '3') }}" placeholder="3">
                <p class="form-hint">注册后多少天未激活则删除</p>
            </div>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">清理闲置用户</span>
                    <span class="settings-field-row-hint">长期未登录的账号自动删除（原版同名设置）</span>
                </span>
                <input type="checkbox" name="auto_delete_inactive_users" value="1" class="input-toggle"
                    {{ filter_var($settings['users.auto_delete_inactive_users'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">删除前提醒</span>
                    <span class="settings-field-row-hint">清理前向用户发送提醒邮件</span>
                </span>
                <input type="checkbox" name="user_deletion_reminder" value="1" class="input-toggle"
                    {{ filter_var($settings['users.user_deletion_reminder'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">注册黑名单</h3>
                <p class="settings-section-desc">按域名 / IP / 地区拦截</p>
            </div>
        </div>
        <div class="settings-section-body">
            <div>
                <label class="form-label">邮箱域名黑名单</label>
                <textarea name="blacklisted_domains" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_domains', $settings['users.blacklisted_domains'] ?? '') }}</textarea>
                <p class="form-hint">每行一个域名，如 mailinator.com</p>
            </div>
            <div>
                <label class="form-label">IP 黑名单</label>
                <textarea name="blacklisted_ips" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_ips', $settings['users.blacklisted_ips'] ?? '') }}</textarea>
                <p class="form-hint">每行一个 IP 或 CIDR 段</p>
            </div>
            <div>
                <label class="form-label">地区黑名单</label>
                <textarea name="blacklisted_countries" rows="4" class="form-input w-full font-mono text-[13px]">{{ old('blacklisted_countries', $settings['users.blacklisted_countries'] ?? '') }}</textarea>
                <p class="form-hint">两位国家代码，逗号分隔，如 CN,US</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">登录防爆破</h3>
                <p class="settings-section-desc">连续失败锁定（原版 login_lockout）</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用登录锁定</span>
                    <span class="settings-field-row-hint">连续失败后临时锁定该账号</span>
                </span>
                <input type="checkbox" name="login_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.login_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">最大失败次数</label>
                <input type="number" name="login_lockout_max_retries" class="form-input" value="{{ old('login_lockout_max_retries', $settings['users.login_lockout_max_retries'] ?? '5') }}" placeholder="5">
                <p class="form-hint">超过该次数触发锁定</p>
            </div>
            <div>
                <label class="form-label">锁定时长（分钟）</label>
                <input type="number" name="login_lockout_time" class="form-input" value="{{ old('login_lockout_time', $settings['users.login_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">锁定持续多久后自动解锁</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">找回密码防爆破</h3>
                <p class="settings-section-desc">原版 lost_password_lockout</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用找回密码锁定</span>
                    <span class="settings-field-row-hint">连续请求找回密码后临时锁定</span>
                </span>
                <input type="checkbox" name="lost_password_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.lost_password_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">最大请求次数</label>
                <input type="number" name="lost_password_lockout_max_retries" class="form-input" value="{{ old('lost_password_lockout_max_retries', $settings['users.lost_password_lockout_max_retries'] ?? '3') }}" placeholder="3">
                <p class="form-hint">超过该次数触发锁定</p>
            </div>
            <div>
                <label class="form-label">锁定时长（分钟）</label>
                <input type="number" name="lost_password_lockout_time" class="form-input" value="{{ old('lost_password_lockout_time', $settings['users.lost_password_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">锁定持续时间</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">重发激活防爆破</h3>
                <p class="settings-section-desc">原版 resend_activation_lockout</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用重发激活锁定</span>
                    <span class="settings-field-row-hint">连续重发激活邮件后临时锁定</span>
                </span>
                <input type="checkbox" name="resend_activation_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.resend_activation_lockout_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">最大重发次数</label>
                <input type="number" name="resend_activation_lockout_max_retries" class="form-input" value="{{ old('resend_activation_lockout_max_retries', $settings['users.resend_activation_lockout_max_retries'] ?? '3') }}" placeholder="3">
                <p class="form-hint">超过该次数触发锁定</p>
            </div>
            <div>
                <label class="form-label">锁定时长（分钟）</label>
                <input type="number" name="resend_activation_lockout_time" class="form-input" value="{{ old('resend_activation_lockout_time', $settings['users.resend_activation_lockout_time'] ?? '30') }}" placeholder="30">
                <p class="form-hint">锁定持续时间</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">注册防爆破</h3>
                <p class="settings-section-desc">原版 register_lockout</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">启用注册限流</span>
                    <span class="settings-field-row-hint">限制单位时间内的注册数量</span>
                </span>
                <input type="checkbox" name="register_lockout_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.register_lockout_is_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
            <div>
                <label class="form-label">最大注册数</label>
                <input type="number" name="register_lockout_max_registrations" class="form-input" value="{{ old('register_lockout_max_registrations', $settings['users.register_lockout_max_registrations'] ?? '5') }}" placeholder="5">
                <p class="form-hint">时限内允许的最大注册数量</p>
            </div>
            <div>
                <label class="form-label">时限（分钟）</label>
                <input type="number" name="register_lockout_time" class="form-input" value="{{ old('register_lockout_time', $settings['users.register_lockout_time'] ?? '60') }}" placeholder="60">
                <p class="form-hint">统计注册数量的时间窗口</p>
            </div>
        </div>
    </section>
    <section class="settings-section">
        <div class="settings-section-header">
            <div>
                <h3 class="settings-section-title">API 访问</h3>
                <p class="settings-section-desc">用户级 API 开关</p>
            </div>
        </div>
        <div class="settings-section-body">
            <label class="settings-field-row">
                <span class="min-w-0">
                    <span class="settings-field-row-label">允许用户使用 API</span>
                    <span class="settings-field-row-hint">用户可通过 API Key 拉取统计数据</span>
                </span>
                <input type="checkbox" name="api_is_enabled" value="1" class="input-toggle"
                    {{ filter_var($settings['users.api_is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </label>
        </div>
    </section>
</div>

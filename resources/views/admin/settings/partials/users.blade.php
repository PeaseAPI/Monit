<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置用户注册与账户相关设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="register_is_enabled" value="1" {{ ($settings['users.register_is_enabled'] ?? true) ? 'checked' : '' }}>
            开放注册
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="email_activation_is_enabled" value="1" {{ ($settings['users.email_activation_is_enabled'] ?? false) ? 'checked' : '' }}>
            邮箱激活（注册后需验证邮箱）
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="auto_delete_unconfirmed_users" value="1" {{ ($settings['users.auto_delete_unconfirmed_users'] ?? false) ? 'checked' : '' }}>
            自动删除未激活用户
        </label>
        <div class="ml-6">
            <label class="form-label">未激活用户保留天数</label>
            <input type="number" name="auto_delete_unconfirmed_users_days" class="form-input w-32" value="{{ old('auto_delete_unconfirmed_users_days', $settings['users.auto_delete_unconfirmed_users_days'] ?? 7) }}">
        </div>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="user_deletion_reminder" value="1" {{ ($settings['users.user_deletion_reminder'] ?? false) ? 'checked' : '' }}>
            账户删除提醒
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="two_fa_is_enabled" value="1" {{ ($settings['users.two_fa_is_enabled'] ?? true) ? 'checked' : '' }}>
            允许用户启用两步验证
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="api_is_enabled" value="1" {{ ($settings['users.api_is_enabled'] ?? true) ? 'checked' : '' }}>
            开放 API 访问
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="user_registration_require_consent" value="1" {{ ($settings['users.user_registration_require_consent'] ?? false) ? 'checked' : '' }}>
            注册时要求同意隐私政策
        </label>
    </div>
</div>


<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置公告/通知设置（规格书 §6.3.1）</p>

    <div class="space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="announcements_is_enabled" value="1" {{ ($settings['announcements.announcements_is_enabled'] ?? false) ? 'checked' : '' }}>
            启用公告横幅
        </label>
        <div>
            <label class="form-label">公告内容</label>
            <textarea name="announcements_content" class="form-input w-full" rows="3">{{ old('announcements_content', $settings['announcements.announcements_content'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="form-label">公告类型</label>
            <select name="announcements_type" class="form-select">
                <option value="info" {{ ($settings['announcements.announcements_type'] ?? 'info') === 'info' ? 'selected' : '' }}>信息</option>
                <option value="warning" {{ ($settings['announcements.announcements_type'] ?? '') === 'warning' ? 'selected' : '' }}>警告</option>
                <option value="success" {{ ($settings['announcements.announcements_type'] ?? '') === 'success' ? 'selected' : '' }}>成功</option>
                <option value="danger" {{ ($settings['announcements.announcements_type'] ?? '') === 'danger' ? 'selected' : '' }}>危险</option>
            </select>
        </div>
    </div>
</div>


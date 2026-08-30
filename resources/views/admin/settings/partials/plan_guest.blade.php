<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置访客套餐（Guest Plan）配额（规格书 §10.1）</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">网站数量上限</label>
            <input type="number" name="websites_limit" class="form-input" value="{{ old('websites_limit', $settings['plan_guest.websites_limit'] ?? 1) }}" min="-1">
            <p class="text-xs text-zinc-500">-1 = 无限制</p>
        </div>
        <div>
            <label class="form-label">会话事件配额/月</label>
            <input type="number" name="sessions_events_limit" class="form-input" value="{{ old('sessions_events_limit', $settings['plan_guest.sessions_events_limit'] ?? 1000) }}" min="-1">
        </div>
        <div>
            <label class="form-label">会话事件留存天数</label>
            <input type="number" name="sessions_events_retention" class="form-input" value="{{ old('sessions_events_retention', $settings['plan_guest.sessions_events_retention'] ?? 7) }}" min="-1">
        </div>
        <div>
            <label class="form-label">每站目标上限</label>
            <input type="number" name="websites_goals_limit" class="form-input" value="{{ old('websites_goals_limit', $settings['plan_guest.websites_goals_limit'] ?? 1) }}" min="-1">
        </div>
    </div>
</div>


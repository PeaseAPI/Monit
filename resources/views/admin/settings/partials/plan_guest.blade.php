<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.plan_guest.desc') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">{{ __('settings.plan_guest.websites_limit') }}</label>
            <input type="number" name="websites_limit" class="form-input" value="{{ old('websites_limit', $settings['plan_guest.websites_limit'] ?? 1) }}" min="-1">
            <p class="text-xs text-zinc-500">{{ __('settings.plan_guest.unlimited_hint') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_guest.sessions_events_limit') }}</label>
            <input type="number" name="sessions_events_limit" class="form-input" value="{{ old('sessions_events_limit', $settings['plan_guest.sessions_events_limit'] ?? 1000) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_guest.sessions_events_retention') }}</label>
            <input type="number" name="sessions_events_retention" class="form-input" value="{{ old('sessions_events_retention', $settings['plan_guest.sessions_events_retention'] ?? 7) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_guest.goals_limit') }}</label>
            <input type="number" name="websites_goals_limit" class="form-input" value="{{ old('websites_goals_limit', $settings['plan_guest.websites_goals_limit'] ?? 1) }}" min="-1">
        </div>
    </div>
</div>


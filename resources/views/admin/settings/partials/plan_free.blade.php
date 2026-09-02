<div class="space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.plan_free.t_da6bc6') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_e7bb15') }}</label>
            <input type="number" name="websites_limit" class="form-input" value="{{ old('websites_limit', $settings['plan_free.websites_limit'] ?? 2) }}" min="-1">
            <p class="text-xs text-zinc-500">{{ __('settings.plan_free.t_f42d1d') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_322031') }}</label>
            <input type="number" name="sessions_events_limit" class="form-input" value="{{ old('sessions_events_limit', $settings['plan_free.sessions_events_limit'] ?? 10000) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_455fa4') }}</label>
            <input type="number" name="sessions_events_retention" class="form-input" value="{{ old('sessions_events_retention', $settings['plan_free.sessions_events_retention'] ?? 30) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_31834f') }}</label>
            <input type="number" name="events_children_limit" class="form-input" value="{{ old('events_children_limit', $settings['plan_free.events_children_limit'] ?? 10000) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_81c3ae') }}</label>
            <input type="number" name="events_children_retention" class="form-input" value="{{ old('events_children_retention', $settings['plan_free.events_children_retention'] ?? 30) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_e12885') }}</label>
            <input type="number" name="websites_goals_limit" class="form-input" value="{{ old('websites_goals_limit', $settings['plan_free.websites_goals_limit'] ?? 5) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_fdfaa8') }}</label>
            <input type="number" name="annotations_limit" class="form-input" value="{{ old('annotations_limit', $settings['plan_free.annotations_limit'] ?? 10) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_51989b') }}</label>
            <input type="number" name="dashboard_views_limit" class="form-input" value="{{ old('dashboard_views_limit', $settings['plan_free.dashboard_views_limit'] ?? 1) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_bf8171') }}</label>
            <input type="number" name="sessions_replays_limit" class="form-input" value="{{ old('sessions_replays_limit', $settings['plan_free.sessions_replays_limit'] ?? 100) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_5f3650') }}</label>
            <input type="number" name="sessions_replays_retention" class="form-input" value="{{ old('sessions_replays_retention', $settings['plan_free.sessions_replays_retention'] ?? 30) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_3a1ed8') }}</label>
            <input type="number" name="websites_heatmaps_limit" class="form-input" value="{{ old('websites_heatmaps_limit', $settings['plan_free.websites_heatmaps_limit'] ?? 3) }}" min="-1">
        </div>
        <div>
            <label class="form-label">{{ __('settings.plan_free.t_676275') }}</label>
            <input type="number" name="domains_limit" class="form-input" value="{{ old('domains_limit', $settings['plan_free.domains_limit'] ?? 0) }}" min="-1">
        </div>
    </div>
</div>


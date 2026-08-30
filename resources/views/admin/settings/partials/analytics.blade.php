{{-- 分析设置（规格书 §6.3.1 / §5） --}}
<div class="space-y-4">
    <div class="flex items-center gap-3"><input type="checkbox" name="annotations_is_enabled" value="1" {{ ($settings['analytics.annotations_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.annotations_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="sessions_replays_is_enabled" value="1" {{ ($settings['analytics.sessions_replays_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.sessions_replays_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="websites_heatmaps_is_enabled" value="1" {{ ($settings['analytics.websites_heatmaps_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.heatmaps_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="custom_domains_is_enabled" value="1" {{ ($settings['analytics.custom_domains_is_enabled'] ?? 'false') === 'true' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.custom_domains_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="email_reports_is_enabled" value="1" {{ ($settings['analytics.email_reports_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.email_reports_enabled') }}</label></div>
    <div class="flex items-center gap-3"><input type="checkbox" name="dashboard_views_is_enabled" value="1" {{ ($settings['analytics.dashboard_views_is_enabled'] ?? 'true') !== 'false' ? 'checked' : '' }}><label class="text-sm">{{ __('admin.dashboard_views_enabled') }}</label></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.sessions_events_retention') }} ({{ __('admin.days') }})</label>
        <input type="number" name="sessions_events_retention" value="{{ $settings['analytics.sessions_events_retention'] ?? 365 }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm"></div>
    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.sessions_replays_retention') }} ({{ __('admin.days') }})</label>
        <input type="number" name="sessions_replays_retention" value="{{ $settings['analytics.sessions_replays_retention'] ?? 30 }}" class="mt-1 w-full rounded-xl border px-4 py-2.5 text-sm"></div>
</div>

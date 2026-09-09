{{-- 工单系统设置（A4：开关 / 通知邮箱 / 邮件入站） --}}
<div class="space-y-4">
    <div class="flex items-center gap-3">
        <input type="hidden" name="tickets_is_enabled" value="0">
        <input type="checkbox" name="tickets_is_enabled" value="1" id="tickets-enabled"
               {{ old('tickets_is_enabled', ($settings['tickets.tickets_is_enabled'] ?? 'true') !== 'false') ? 'checked' : '' }}>
        <label for="tickets-enabled" class="text-sm">{{ __('settings.tickets.t_enabled') }}</label>
    </div>
    <p class="text-xs text-zinc-400">{{ __('settings.tickets.t_enabled_hint') }}</p>

    <div>
        <label class="block text-sm font-medium text-zinc-700">{{ __('settings.tickets.t_notification_email') }}</label>
        <input type="email" name="notification_email" value="{{ old('notification_email', $settings['tickets.notification_email'] ?? '') }}"
               placeholder="{{ __('settings.tickets.t_notification_email_hint') }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-zinc-400">{{ __('settings.tickets.t_notification_email_desc') }}</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700">{{ __('settings.tickets.t_inbound_email') }}</label>
        <input type="email" name="inbound_email" value="{{ old('inbound_email', $settings['tickets.inbound_email'] ?? '') }}"
               placeholder="support@{{ request()->getHost() }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-zinc-400">{{ __('settings.tickets.t_inbound_email_desc') }}</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-zinc-700">{{ __('settings.tickets.t_webhook_token') }}</label>
        @php($token = $settings['tickets.inbound_webhook_token'] ?? '')
        @if ($token !== '')
            <div class="mt-1 flex items-center gap-2">
                <code class="flex-1 overflow-x-auto rounded-xl bg-zinc-50 px-3 py-2 text-xs text-zinc-700">{{ $token }}</code>
            </div>
            <p class="mt-1 text-xs text-zinc-400">{{ __('settings.tickets.t_webhook_url') }}: <code>POST {{ url('/webhooks/email') }}?token={{ $token }}</code></p>
        @else
            <p class="mt-1 text-xs text-amber-600">{{ __('settings.tickets.t_webhook_token_empty') }}</p>
        @endif
        <p class="mt-2 text-xs text-zinc-400">{{ __('settings.tickets.t_webhook_desc') }}</p>
    </div>
</div>

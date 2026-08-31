<?php

namespace App\Http\Controllers;

use App\Models\NotificationHandler;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SEO 通知处理器控制器（融合方案 §11，设置面）
 * - 8 渠道：email/webhook/slack/discord/telegram/pushover/ntfy/gotify
 * - 4 事件订阅：audit_refreshed/audit_failed/sitemap_changed/domain_expiring
 */
class SeoNotificationHandlerController extends Controller
{
    protected const TYPES = ['email', 'webhook', 'slack', 'discord', 'telegram', 'pushover', 'ntfy', 'gotify'];

    public function index(Request $request): View
    {
        return view('seo.handlers', [
            'handlers' => $request->user()->notificationHandlers()->orderByDesc('notification_handler_id')->get(),
            'types' => self::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:64',
            'type' => 'required|in:'.implode(',', self::TYPES),
            'settings' => 'nullable|array',
            'events' => 'required|array|min:1',
            'events.*' => 'in:audit_refreshed,audit_failed,sitemap_changed,domain_expiring',
        ]);

        $limit = (int) ($request->user()->getPlanSettings()['seo_notifications_limit'] ?? -1);
        $count = $request->user()->notificationHandlers()->count();

        if ($limit >= 0 && $count >= $limit) {
            return back()->withErrors(['name' => __('seo.quota_exceeded')]);
        }

        $request->user()->notificationHandlers()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'settings' => $this->settingsFor($validated['type'], $validated['settings'] ?? [])
                + ['events' => array_values($validated['events'])],
            'is_enabled' => true,
        ]);

        return back()->with('success', __('seo.saved'));
    }

    public function update(Request $request, NotificationHandler $handler)
    {
        $this->authorizeOwner($request, $handler);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:64',
            'settings' => 'sometimes|array',
            'events' => 'sometimes|array',
            'events.*' => 'in:audit_refreshed,audit_failed,sitemap_changed,domain_expiring',
            'is_enabled' => 'sometimes|boolean',
        ]);

        $settings = $handler->settings ?? [];

        if (isset($validated['settings'])) {
            $settings = $this->settingsFor($handler->type, $validated['settings']) + $settings;
        }

        if (isset($validated['events'])) {
            $settings['events'] = array_values($validated['events']);
        }

        $handler->update(array_filter([
            'name' => $validated['name'] ?? null,
            'settings' => $settings ?: null,
            'is_enabled' => array_key_exists('is_enabled', $validated)
                ? (bool) $validated['is_enabled']
                : null,
        ], fn ($v) => $v !== null));

        return back()->with('success', __('seo.saved'));
    }

    public function destroy(Request $request, NotificationHandler $handler)
    {
        $this->authorizeOwner($request, $handler);

        $handler->delete();

        return back()->with('success', __('seo.deleted'));
    }

    protected function settingsFor(string $type, array $raw): array
    {
        $allowed = match ($type) {
            'email' => [],
            'webhook', 'slack', 'discord' => ['webhook_url'],
            'telegram' => ['bot_token', 'chat_id'],
            'pushover' => ['api_token', 'user_key'],
            'ntfy' => ['server', 'topic'],
            'gotify' => ['server', 'app_token'],
            default => [],
        };

        return collect($raw)->only($allowed)->map(fn ($v) => (string) $v)->all();
    }

    protected function authorizeOwner(Request $request, NotificationHandler $handler): void
    {
        if ((int) $handler->user_id !== (int) $request->user()->user_id && ! $request->user()->isAdmin()) {
            abort(403);
        }
    }
}

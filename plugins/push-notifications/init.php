<?php

/**
 * Push Notifications 启动入口（规格书 §14.5）
 * - POST /push-notifications/subscribe 订阅收集
 * - GET  /push-notifications/sw.js     插件 Service Worker
 * - GET  /push-notifications/js        嵌入用户站点的订阅脚本
 * - Admin Campaign 管理见后半段
 */

use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationSubscriber;
use App\Models\Website;
use App\Services\WebPushService;
use App\Support\PluginManager;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Settings::set('push_notifications.is_enabled', true);

/* ---------------- 订阅端点 ---------------- */

Route::post('/push-notifications/subscribe', function (Request $request) {
    if (! PluginManager::isActive('push-notifications')) {
        abort(404);
    }

    $validated = $request->validate([
        'website_id' => ['nullable', 'integer'],
        'host' => ['nullable', 'string', 'max:256'],
        'endpoint' => ['required', 'string', 'max:2048'],
        'keys.p256dh' => ['required', 'string', 'max:512'],
        'keys.auth' => ['required', 'string', 'max:256'],
    ]);

    // 归属网站：优先 website_id，其次按 host 精确匹配
    $website = null;
    if (! empty($validated['website_id'])) {
        $website = Website::find($validated['website_id']);
    } elseif (! empty($validated['host'])) {
        $website = Website::where('host', strtolower(preg_replace('#^https?://#i', '', $validated['host'])))
            ->orderBy('website_id')->first();
    }

    if (! $website) {
        return response()->json(['error' => 'website_not_found'], 404);
    }

    PushNotificationSubscriber::updateOrCreate(
        ['endpoint' => $validated['endpoint']],
        [
            'website_id' => $website->website_id,
            'user_id' => $website->user_id,
            'keys_p256dh' => $validated['keys']['p256dh'],
            'keys_auth' => $validated['keys']['auth'],
            'ip' => $request->ip(),
            'subscriber_datetime' => now(),
        ],
    );

    return response()->json(['success' => true]);
})->name('push-notifications.subscribe');

/* ---------------- 插件 Service Worker ---------------- */

Route::get('/push-notifications/sw.js', function () {
    if (! PluginManager::isActive('push-notifications')) {
        abort(404);
    }

    $js = <<<'JS'
self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data.json(); } catch (e) {}

    event.waitUntil(self.registration.showNotification(data.title || 'Monit', {
        body: data.body || '',
        icon: data.icon || '/favicon.ico',
        data: { url: data.url || '/' },
        tag: 'monit-push',
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(clients.matchAll({ type: 'window' }).then((list) => {
        for (const client of list) {
            if (client.url.includes(url) && 'focus' in client) return client.focus();
        }
        return clients.openWindow(url);
    }));
});
JS;

    return response($js, 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/push-notifications/',
    ]);
})->name('push-notifications.sw');

/* ---------------- 嵌入脚本（用户站点使用） ---------------- */

Route::get('/push-notifications/js', function () {
    if (! PluginManager::isActive('push-notifications')) {
        abort(404);
    }

    $vapidKey = PluginManager::setting('push-notifications', 'vapid_public_key', '');
    $origin = rtrim(config('app.url'), '/');

    $js = <<<JS
(function () {
    var ORIGIN = '{$origin}';
    var VAPID = '{$vapidKey}';

    function urlB64ToUint8Array(b64) {
        var padding = '='.repeat((4 - b64.length % 4) % 4);
        var raw = atob((b64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    window.MonitPush = {
        subscribe: function () {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                return Promise.reject(new Error('unsupported'));
            }
            return navigator.serviceWorker.register(ORIGIN + '/push-notifications/sw.js', { scope: '/push-notifications/' })
                .then(function (reg) { return reg.pushManager.getSubscription().then(function (sub) {
                    return sub || reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlB64ToUint8Array(VAPID),
                    });
                }); })
                .then(function (sub) {
                    return fetch(ORIGIN + '/push-notifications/subscribe', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ host: location.hostname, endpoint: sub.endpoint, keys: sub.toJSON().keys }),
                    });
                });
        },
    };
})();
JS;

    return response($js, 200, ['Content-Type' => 'application/javascript']);
})->name('push-notifications.js');

/* ---------------- Admin：Campaign 管理（规格书 §9.2 AdminPushNotifications） ---------------- */

Route::middleware(['auth', 'admin'])->prefix('admin/plugins/push-notifications')->group(function (): void {
    // 密钥对生成（一次性）
    Route::post('/generate-keys', function (Request $request) {
        if (! PluginManager::isActive('push-notifications')) {
            abort(404);
        }

        $keys = WebPushService::generateVapidKeys();
        PluginManager::saveSettings('push-notifications', $keys);

        return back()->with('success', 'VAPID 密钥对已生成：公钥 '.substr($keys['public_key'], 0, 24).'…');
    })->name('admin.plugins.push-notifications.generate-keys');

    // Campaign 列表 + 订阅者统计
    Route::get('/campaigns', function () {
        if (! PluginManager::isActive('push-notifications')) {
            abort(404);
        }

        $campaigns = PushNotificationCampaign::with('website')
            ->orderByDesc('campaign_id')->limit(100)->get();
        $subscribersTotal = PushNotificationSubscriber::count();

        return view('plugins.push-notifications.admin-campaigns', compact('campaigns', 'subscribersTotal'))
            ->with('adminNav', 'plugins');
    })->name('admin.plugins.push-notifications.campaigns');

    // Campaign 创建（默认 is_sent=false，由 Cron 分批发送）
    Route::post('/campaigns', function (Request $request) {
        if (! PluginManager::isActive('push-notifications')) {
            abort(404);
        }

        $validated = $request->validate([
            'website_id' => ['required', 'integer', 'exists:websites,website_id'],
            'name' => ['required', 'string', 'max:256'],
            'title' => ['required', 'string', 'max:256'],
            'description' => ['nullable', 'string', 'max:4096'],
            'url' => ['nullable', 'url', 'max:2048'],
            'icon' => ['nullable', 'url', 'max:2048'],
        ]);

        PushNotificationCampaign::create([
            ...$validated,
            'is_enabled' => true,
            'is_sent' => false,
            'datetime' => now(),
        ]);

        return back()->with('success', 'Campaign 已创建，等待 Cron 发送');
    })->name('admin.plugins.push-notifications.campaigns.store');

    // 手动触发发送（把 enabled+未发送的置为待发送标记后由命令处理；此处直接同步发送小批量）
    Route::post('/campaigns/{campaignId}/send', function (string $campaignId) {
        if (! PluginManager::isActive('push-notifications')) {
            abort(404);
        }

        $campaign = PushNotificationCampaign::findOrFail($campaignId);

        if ($campaign->is_sent) {
            return back()->with('error', '该 Campaign 已发送');
        }

        Artisan::call('monit:push-notifications-campaigns', [
            'campaignId' => $campaign->campaign_id,
        ]);

        return back()->with('success', '发送完成：成功 '.$campaign->refresh()->total_sent.' / 失败 '.$campaign->refresh()->total_failed);
    })->name('admin.plugins.push-notifications.campaigns.send');

    Route::delete('/campaigns/{campaignId}', function (string $campaignId) {
        if (! PluginManager::isActive('push-notifications')) {
            abort(404);
        }

        PushNotificationCampaign::findOrFail($campaignId)->delete();

        return back()->with('success', 'Campaign 已删除');
    })->name('admin.plugins.push-notifications.campaigns.destroy');
});

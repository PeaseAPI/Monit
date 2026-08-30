<?php

namespace App\Http\Controllers;

use App\Models\PushNotificationSubscriber;
use App\Support\PluginManager;
use Illuminate\Http\RedirectResponse;

/**
 * 管理后台 - Push 订阅者管理（插件 push-notifications）
 * 规格书 §6.3.4 / 附B：AdminPushSubscribers
 */
class AdminPushSubscribers extends Controller
{
    public function index()
    {
        // 插件未启用时引导到插件页
        if (! PluginManager::isActive('push-notifications')) {
            return redirect()->route('admin.plugins.index')
                            ->with('error', __('admin.push_subscribers_plugin_required'));
        }

        $subscribers = PushNotificationSubscriber::with('website')
            ->orderByDesc('subscriber_id')
            ->paginate(50);

        return view('admin.push-subscribers.index', compact('subscribers'))->with('adminNav', 'push-subscribers');
    }

    public function destroy(int $subscriberId): RedirectResponse
    {
        PushNotificationSubscriber::findOrFail($subscriberId)->delete();

        return redirect()->route('admin.push-subscribers.index')
                        ->with('success', __('msg.push_subscriber_deleted'));
    }
}

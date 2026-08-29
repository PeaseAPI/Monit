<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 用户中心 - 内部通知
 * 规格书 §6.2.6：InternalNotifications
 */
class InternalNotificationsController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->internalNotifications()
            ->orderByDesc('datetime')
            ->paginate(50);

        return view('internal_notifications.index', compact('notifications'));
    }

    public function markAsRead(int $notificationId): RedirectResponse
    {
        $notification = InternalNotification::findOrFail($notificationId);
        $notification->update(['is_read' => true]);

                return back()->with('success', __('msg.notification_read'));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->internalNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

                return back()->with('success', __('msg.all_notifications_read'));
    }

    public function destroy(int $notificationId): RedirectResponse
    {
        $notification = InternalNotification::findOrFail($notificationId);
        $notification->delete();

                return back()->with('success', __('msg.notification_deleted'));
    }
}

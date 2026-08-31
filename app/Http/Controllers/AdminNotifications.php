<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use App\Models\PushNotificationCampaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 站内通知管理
 * 规格书 §6.3.4 / 附B：AdminInternalNotifications / AdminInternalNotificationCreate
 */
class AdminNotifications extends Controller
{
    public function index()
    {
        $notifications = InternalNotification::with('user')->orderByDesc('internal_notification_id')->paginate(25);

        return view('admin.notifications.index', compact('notifications'))->with('adminNav', 'notifications');
    }

    public function create()
    {
        return view('admin.notifications.form')->with('adminNav', 'notifications');
    }

    /**
     * 群发站内通知：向全部活跃用户（或指定邮箱用户）写入 internal_notifications
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:256'],
            'message' => ['required', 'string', 'max:2048'],
            'target_email' => ['nullable', 'email'],
        ]);

        $data = ['title' => $validated['title'], 'message' => $validated['message']];
        $adminUserId = auth()->user()->user_id;

        $query = User::where('status', 1);
        if ($validated['target_email'] ?? null) {
            $query->where('email', $validated['target_email']);
        }

        $count = 0;
        $query->chunkById(100, function ($users) use (&$count, $data, $adminUserId): void {
            foreach ($users as $user) {
                InternalNotification::create([
                    'user_id' => $user->user_id,
                    'from_user_id' => $adminUserId,
                    'for_type' => 'admin',
                    'for_id' => 0,
                    'data' => $data,
                    'is_read' => false,
                    'datetime' => now(),
                ]);
                $count++;
            }
        }, 'user_id');

        if ($count === 0) {
            return redirect()->route('admin.notifications.index')
                ->with('error', __('msg.notification_no_recipients'));
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', __('msg.notification_sent', ['count' => $count]));
    }

    public function destroy(int $notificationId): RedirectResponse
    {
        InternalNotification::findOrFail($notificationId)->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', __('msg.notification_deleted'));
    }

    // ========================================
    // Push Notification Campaign 管理（规格书 §14.5）
    // ========================================

    public function pushIndex()
    {
        $campaigns = PushNotificationCampaign::orderByDesc('push_notification_campaign_id')->paginate(25);

        return view('admin.push-notifications.index', compact('campaigns'))->with('adminNav', 'push-notifications');
    }

    public function pushCreate()
    {
        return view('admin.push-notifications.create')->with('adminNav', 'push-notifications');
    }

    public function pushStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'title' => ['required', 'string', 'max:256'],
            'body' => ['required', 'string', 'max:2048'],
            'url' => ['nullable', 'url', 'max:2048'],
        ]);

        PushNotificationCampaign::create([
            ...$validated,
            'status' => 'pending',
            'datetime' => now(),
        ]);

        return redirect()->route('admin.push-notifications.index')
            ->with('success', __('msg.campaign_created'));
    }

    public function pushEdit(int $campaign)
    {
        $campaign = PushNotificationCampaign::findOrFail($campaign);

        return view('admin.push-notifications.edit', compact('campaign'))->with('adminNav', 'push-notifications');
    }

    public function pushUpdate(Request $request, int $campaign): RedirectResponse
    {
        $campaign = PushNotificationCampaign::findOrFail($campaign);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:256'],
            'title' => ['required', 'string', 'max:256'],
            'body' => ['required', 'string', 'max:2048'],
            'url' => ['nullable', 'url', 'max:2048'],
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.push-notifications.index')
            ->with('success', __('msg.campaign_updated'));
    }

    public function pushDestroy(int $campaign): RedirectResponse
    {
        PushNotificationCampaign::findOrFail($campaign)->delete();

        return redirect()->route('admin.push-notifications.index')
            ->with('success', __('msg.campaign_deleted'));
    }
}

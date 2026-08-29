<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
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
}

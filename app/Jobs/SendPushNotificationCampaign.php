<?php

namespace App\Jobs;

use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationSubscriber;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 发送推送通知任务（规格书 §14.5：push_notifications_campaigns）
 */
class SendPushNotificationCampaign implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public PushNotificationCampaign $campaign) {}

    public function handle(): void
    {
        $subscribers = PushNotificationSubscriber::when(
            $this->campaign->target_users,
            fn ($q) => $q->whereIn('user_id', $this->campaign->target_users)
        )->chunk(100, function ($subscribers) {
            foreach ($subscribers as $subscriber) {
                try {
                    app(WebPushService::class)->sendOne(
                        $subscriber,
                        $this->campaign->title,
                        $this->campaign->content,
                        $this->campaign->url,
                    );
                } catch (\Throwable $e) {
                    Log::warning('Push notification failed', [
                        'subscriber_id' => $subscriber->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->campaign->update([
            'status' => 'sent',
            'sent_datetime' => now(),
        ]);
    }
}

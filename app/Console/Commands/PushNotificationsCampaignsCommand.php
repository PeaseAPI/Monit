<?php

namespace App\Console\Commands;

use App\Models\PushNotificationCampaign;
use App\Models\PushNotificationSubscriber;
use App\Services\WebPushService;
use App\Support\PluginManager;
use Illuminate\Console\Command;

/**
 * Push Notifications Campaign 发送 Cron（规格书 §13.1 push_notifications_campaigns）
 * 每分钟运行；插件未启用时直接退出。分批发送 pending Campaign，清理失效订阅。
 */
class PushNotificationsCampaignsCommand extends Command
{
    protected $signature = 'monit:push-notifications-campaigns
        {campaignId? : 仅发送指定 Campaign}';

    protected $description = '发送待处理的 Push Campaign（Web Push，分批）';

    protected int $totalSent = 0;

    protected int $totalFailed = 0;

    protected int $expiredRemoved = 0;

    public function handle(): int
    {
        if (! PluginManager::isActive('push-notifications')) {
            $this->info('push-notifications 插件未启用，跳过');

            return self::SUCCESS;
        }

        $publicKey = (string) PluginManager::setting('push-notifications', 'vapid_public_key', '');
        $privateKey = (string) PluginManager::setting('push-notifications', 'vapid_private_key', '');
        $subject = (string) PluginManager::setting('push-notifications', 'subject', 'mailto:admin@example.com');
        $batchSize = max(1, (int) PluginManager::setting('push-notifications', 'batch_size', 100));

        if ($publicKey === '' || $privateKey === '') {
            $this->error('VAPID 密钥未配置（插件设置中生成）');

            return self::FAILURE;
        }

        $query = PushNotificationCampaign::where('is_enabled', true)
            ->where('is_sent', false)
            ->orderBy('campaign_id');

        if ($campaignId = $this->argument('campaignId')) {
            $query->where('campaign_id', (int) $campaignId);
        }

        $campaigns = $query->limit(5)->get();

        if ($campaigns->isEmpty()) {
            $this->info('无待发送 Campaign');

            return self::SUCCESS;
        }

        $pusher = new WebPushService;

        foreach ($campaigns as $campaign) {
            $this->totalSent = 0;
            $this->totalFailed = 0;
            $this->expiredRemoved = 0;

            PushNotificationSubscriber::where('website_id', $campaign->website_id)
                ->orderBy('subscriber_id')
                ->chunk($batchSize, function ($subscribers) use ($pusher, $campaign, $publicKey, $privateKey, $subject) {
                    foreach ($subscribers as $subscriber) {
                        $ok = $pusher->send(
                            $subscriber->endpoint,
                            $subscriber->keys_p256dh,
                            $subscriber->keys_auth,
                            [
                                'title' => $campaign->title,
                                'body' => $campaign->description,
                                'url' => $campaign->url,
                                'icon' => $campaign->icon,
                            ],
                            $publicKey,
                            $privateKey,
                            $subject,
                        );

                        $ok ? $this->totalSent++ : $this->totalFailed++;

                        // 404/410：订阅已失效，清理
                        if ($pusher->lastResults['expired'] ?? false) {
                            $subscriber->delete();
                            $this->expiredRemoved++;
                        }
                    }
                });

            $campaign->update([
                'is_sent' => true,
                'sent_datetime' => now(),
                'total_sent' => $this->totalSent,
                'total_failed' => $this->totalFailed,
            ]);

            $this->info("Campaign #{$campaign->campaign_id} [{$campaign->name}]：成功 {$this->totalSent}，失败 {$this->totalFailed}，清理失效订阅 {$this->expiredRemoved}");
        }

        return self::SUCCESS;
    }
}

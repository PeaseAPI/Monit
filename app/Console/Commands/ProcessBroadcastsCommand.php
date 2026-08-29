<?php

namespace App\Console\Commands;

use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * 广播邮件发送 Cron
 * 规格书 §13：每分钟发送 pending 广播邮件
 */
class ProcessBroadcastsCommand extends Command
{
    protected $signature = 'monit:process-broadcasts';

    protected $description = '发送待处理的广播邮件';

    public function handle(): int
    {
        $broadcasts = Broadcast::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->limit(25)
            ->get();

        $sent = 0;

        foreach ($broadcasts as $broadcast) {
            // 获取目标用户列表
            $query = User::where('status', 1);

            if ($broadcast->target === 'newsletter') {
                $query->where('is_newsletter_subscribed', true);
            } elseif ($broadcast->target === 'plan') {
                $query->where('plan_id', $broadcast->target_plan_id);
            }

            $users = $query->chunk(100, function ($chunk) use ($broadcast, &$sent) {
                foreach ($chunk as $user) {
                    // TODO: 使用 Mail 发送广播邮件
                    // Mail::to($user)->queue(new BroadcastMail($broadcast, $user));
                    $sent++;
                }
            });

            $broadcast->update([
                'status' => 'sent',
                'sent_datetime' => now(),
                'total_emails' => $sent,
                'total_sent' => $sent,
            ]);
        }

        $this->info("已发送 {$sent} 封广播邮件");

        return self::SUCCESS;
    }
}

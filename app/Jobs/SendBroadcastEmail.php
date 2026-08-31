<?php

namespace App\Jobs;

use App\Mail\BroadcastMessage;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 发送广播邮件任务（规格书 §13.1：broadcasts）
 */
class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public Broadcast $broadcast,
        public User $recipient,
    ) {}

    public function handle(): void
    {
        Mail::to($this->recipient)->send(new BroadcastMessage($this->broadcast, $this->recipient));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Broadcast email failed', [
            'broadcast_id' => $this->broadcast->broadcast_id,
            'recipient' => $this->recipient->email,
            'error' => $exception->getMessage(),
        ]);
    }
}

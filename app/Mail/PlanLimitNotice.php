<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 站点配额超限通知（原版 websites_*_notice，规格书 §13.1 M22）
 * scene: sessions_events / events_children / sessions_replays
 */
class PlanLimitNotice extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public Website $website,
        public string $scene,
        public int $limit,
        public int $current
    ) {}

    public function build(): static
    {
        return $this->subject(__('msg.plan_limit_notice_subject', ['website' => $this->website->name]))
            ->view('emails.plan-limit-notice')
            ->with([
                'user' => $this->user,
                'website' => $this->website,
                'scene' => $this->scene,
                'limit' => $this->limit,
                'current' => $this->current,
            ]);
    }
}

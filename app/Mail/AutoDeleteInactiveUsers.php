<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 不活跃用户删除通知（原版 auto_delete_inactive_users，规格书 §13.1 M22）
 */
class AutoDeleteInactiveUsers extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public int $inactivityDays) {}

    public function build(): static
    {
        return $this->subject(__('msg.auto_delete_inactive_users_subject', ['days' => $this->inactivityDays]))
            ->view('emails.auto-delete-inactive-users')
            ->with([
                'user' => $this->user,
                'days' => $this->inactivityDays,
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 不活跃用户删除提醒（原版 users_deletion_reminder，规格书 §13.1 M22）
 */
class UserDeletionReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public int $daysUntilDeletion) {}

    public function build(): static
    {
        return $this->subject(__('msg.user_deletion_reminder_subject', ['days' => $this->daysUntilDeletion]))
            ->view('emails.user-deletion-reminder')
            ->with([
                'user' => $this->user,
                'days' => $this->daysUntilDeletion,
            ]);
    }
}

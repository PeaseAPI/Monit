<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 新用户注册通知（发送给管理员）（规格书 §6.3.1：email_notifications_new_user）
 */
class NewUserNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $newUser) {}

    public function build(): static
    {
        return $this->subject(__('msg.new_user_notification_subject', ['name' => $this->newUser->name]))
            ->markdown('emails.new-user-notification')
            ->with(['newUser' => $this->newUser]);
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 邮箱激活邮件（规格书 §12：email_activation_code）
 */
class ActivateUser extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user, public string $activationUrl) {}

    public function build(): static
    {
        return $this->subject(__('msg.activate_account_subject'))
            ->markdown('emails.activate-user')
            ->with(['user' => $this->user, 'activationUrl' => $this->activationUrl]);
    }
}

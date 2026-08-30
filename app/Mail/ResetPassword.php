<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 密码重置邮件（规格书 §6.1：/lost-password、/reset-password）
 */
class ResetPassword extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $email, public string $resetUrl) {}

    public function build(): static
    {
        return $this->subject(__('msg.reset_password_subject'))
            ->view('emails.reset-password')
            ->with(['email' => $this->email, 'resetUrl' => $this->resetUrl]);
    }
}

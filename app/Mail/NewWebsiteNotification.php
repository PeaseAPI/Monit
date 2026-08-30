<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 管理员新站点通知邮件（规格书 §6.3：email_notifications_new_website）
 */
class NewWebsiteNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $websiteName,
        public string $websiteHost,
        public string $userName,
        public string $userEmail,
    ) {}

    public function build(): static
    {
        return $this->subject(__('mail.new_website_subject', ['name' => $this->websiteName]))
            ->view('emails.new-website', [
                'websiteName' => $this->websiteName,
                'websiteHost' => $this->websiteHost,
                'userName' => $this->userName,
                'userEmail' => $this->userEmail,
            ]);
    }
}

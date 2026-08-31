<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * SEO 事件通知邮件（复用平台 SMTP 设置）
 */
class SeoNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $link = null,
    ) {}

    public function build(): static
    {
        return $this->subject($this->title)
            ->view('emails.seo_notification');
    }
}

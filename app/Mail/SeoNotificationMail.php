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
        // 命名不能叫 $message：Laravel 邮件视图永远注入 $message（Illuminate\Mail\Message，
        // 供内嵌附件用），同名属性会被遮蔽，{{ $message }} 渲染将触发
        // htmlspecialchars(): Argument #1 must be of type string, Message given
        public readonly string $body,
        public readonly ?string $link = null,
    ) {}

    public function build(): static
    {
        return $this->subject($this->title)
            ->view('emails.seo_notification');
    }
}

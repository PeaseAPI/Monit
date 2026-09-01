<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 欢迎邮件（对标原版 users.welcome_email_is_enabled）
 * 注册成功后发送（激活流程下激活完成时发送），内容含站点名/登录入口
 */
class WelcomeUser extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user) {}

    public function build(): static
    {
        return $this->subject(__('msg.welcome_email_subject', ['site' => \App\Support\Brand::name()]))
            ->view('emails.welcome-user')
            ->with([
                'user' => $this->user,
                'loginUrl' => route('login'),
                'siteName' => \App\Support\Brand::name(),
            ]);
    }
}

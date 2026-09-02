<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 套餐降级通知邮件（规格书 §13.1：users_plan_expiration 降级后通知）
 */
class PlanDowngraded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function build(): static
    {
        return $this->subject(__('mail.plan_downgraded_subject'))
            ->markdown('emails.plan-downgraded', [
                'user' => $this->user,
            ]);
    }
}

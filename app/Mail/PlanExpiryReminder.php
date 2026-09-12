<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 套餐过期提醒邮件（规格书 §13.1：users_plan_expiry_reminder）
 */
class PlanExpiryReminder extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $user) {}

    public function build(): static
    {
        return $this->subject(__('msg.plan_expiry_reminder_subject'))
            ->markdown('emails.plan-expiry-reminder')
            ->with([
                'user' => $this->user,
                // 套餐显示名称而非 plan_id 数字；套餐被删时回退 plan_id
                'planName' => $this->user->plan?->name ?? (string) $this->user->plan_id,
                'expirationDate' => $this->user->plan_expiration_date,
            ]);
    }
}

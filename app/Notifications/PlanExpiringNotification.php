<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 套餐即将过期通知（规格书 §13.1：users_plan_expiry_reminder）
 */
class PlanExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $planName,
        public string $expirationDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('msg.plan_expiring_subject'))
            ->line(__('msg.plan_expiring_body', [
                'plan' => $this->planName,
                'date' => $this->expirationDate,
            ]))
            ->action(__('msg.renew_plan'), route('account.plan'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'plan_expiring',
            'title' => __('msg.plan_expiring_subject'),
            'message' => __('msg.plan_expiring_body', [
                'plan' => $this->planName,
                'date' => $this->expirationDate,
            ]),
            'url' => route('account.plan'),
        ];
    }
}

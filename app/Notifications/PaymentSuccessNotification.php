<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 支付成功通知（规格书 §10：支付确认）
 */
class PaymentSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $planName,
        public float $amount,
        public string $currency,
        public ?string $frequency = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('msg.payment_success_subject'))
            ->line(__('msg.payment_success_body', [
                'plan' => $this->planName,
                'amount' => $this->amount,
                'currency' => $this->currency,
            ]))
            ->action(__('msg.view_plan'), route('account.plan'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'payment_success',
            'title' => __('msg.payment_success_subject'),
            'message' => __('msg.payment_success_body', [
                'plan' => $this->planName,
                'amount' => $this->amount,
                'currency' => $this->currency,
            ]),
            'url' => route('account.plan'),
        ];
    }
}

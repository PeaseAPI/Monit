<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 新支付通知（发送给管理员）（规格书 §6.3.1：email_notifications_new_payment）
 */
class NewPaymentNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Payment $payment) {}

    public function build(): static
    {
        return $this->subject(__('msg.new_payment_notification_subject'))
            ->markdown('emails.new-payment-notification')
            ->with(['payment' => $this->payment]);
    }
}

<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 支付确认邮件（规格书 §10：支付成功通知）
 */
class PaymentConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Payment $payment, public User $user) {}

    public function build(): static
    {
        return $this->subject(__('msg.payment_confirmation_subject'))
            ->markdown('emails.payment-confirmation')
            ->with([
                'payment' => $this->payment,
                'user' => $this->user,
            ]);
    }
}

<?php

namespace App\Jobs;

use App\Mail\NewPaymentNotification;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * 通知管理员新支付（规格书 §6.3.1：email_notifications_new_payment）
 */
class NotifyAdminNewPayment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Payment $payment) {}

    public function handle(): void
    {
        $admins = User::where('type', 1)->get();

        foreach ($admins as $admin) {
            Mail::to($admin)->send(new NewPaymentNotification($this->payment));
        }
    }
}

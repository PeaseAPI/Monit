<?php

namespace App\Support;

use App\Mail\TicketSubmittedToAdmin;
use App\Mail\TicketUserRepliedToAdmin;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * 工单通知（A4）：提交/客户回复 → 通知管理员
 * 收件人：settings tickets.notification_email 优先；未配置则发送给全部管理员（type=1）
 */
class TicketNotifications
{
    public static function notifyAdminsTicketCreated(Ticket $ticket, string $message): void
    {
        self::toAdmins(new TicketSubmittedToAdmin($ticket, $message));
    }

    /**
     * @param  TicketReply  $reply
     */
    public static function notifyAdminsTicketReplied(Ticket $ticket, $reply): void
    {
        self::toAdmins(new TicketUserRepliedToAdmin($ticket, $reply));
    }

    /**
     * @param  Mailable  $mailable
     */
    protected static function toAdmins($mailable): void
    {
        $custom = trim(Typed::string(Settings::get('tickets.notification_email', '')));

        if ($custom !== '') {
            Mail::to($custom)->send($mailable);

            return;
        }

        User::query()->where('type', 1)->get()
            ->each(fn (User $admin) => Mail::to($admin)->send($mailable));
    }
}

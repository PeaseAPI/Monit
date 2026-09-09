<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 工单回复通知（发送给提交人）：管理员在后台回复了工单
 */
class TicketRepliedToUser extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket, public TicketReply $reply) {}

    public function build(): static
    {
        // Reply-To 留站内收件箱（配置了入站邮箱时），管理员直接回邮件即入工单
        $inbound = trim((string) \App\Support\Settings::get('tickets.inbound_email', ''));
        $mailable = $this->subject('Re: ['.$this->ticket->code().'] '.$this->ticket->subject)
            ->markdown('emails.ticket-message')
            ->with([
                'ticket' => $this->ticket,
                'heading' => __('msg.ticket_user_replied_heading'),
                'message' => $this->reply->message,
                'actionUrl' => \App\Support\Settings::get('tickets.inbound_email') !== null && $this->ticket->user_id
                    ? route('tickets.show', $this->ticket->ticket_id)
                    : route('login'),
                'actionText' => __('msg.ticket_user_view'),
                'footerNote' => __('msg.ticket_user_direct_reply_note'),
            ]);

        if ($inbound !== '') {
            $mailable->replyTo($inbound);
        }

        return $mailable;
    }
}

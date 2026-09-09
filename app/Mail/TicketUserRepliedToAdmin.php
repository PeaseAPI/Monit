<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 工单新回复通知（发送给管理员）：客户通过页面或回信追加了内容
 */
class TicketUserRepliedToAdmin extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket, public TicketReply $reply) {}

    public function build(): static
    {
        return $this->subject('[Monit] 工单新回复 '.$this->ticket->code().'：'.$this->ticket->subject)
            ->markdown('emails.ticket-message')
            ->with([
                'ticket' => $this->ticket,
                'heading' => __('msg.ticket_admin_followup_heading'),
                'message' => $this->reply->message,
                'actionUrl' => route('admin.tickets.show', $this->ticket->ticket_id),
                'actionText' => __('msg.ticket_admin_view'),
                'footerNote' => __('msg.ticket_admin_reply_note'),
            ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 新工单通知（发送给管理员）（A4 工单系统）
 */
class TicketSubmittedToAdmin extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Ticket $ticket, public string $message) {}

    public function build(): static
    {
        return $this->subject('[Monit] 新工单 '.$this->ticket->code().'：'.$this->ticket->subject)
            ->markdown('emails.ticket-message')
            ->with([
                'ticket' => $this->ticket,
                'heading' => __('msg.ticket_admin_new_heading'),
                'message' => $this->message,
                'actionUrl' => route('admin.tickets.show', $this->ticket->ticket_id),
                'actionText' => __('msg.ticket_admin_view'),
                'footerNote' => __('msg.ticket_admin_reply_note'),
            ]);
    }
}

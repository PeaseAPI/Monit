<?php

namespace App\Mail;

use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 广播邮件（规格书 §6.3.4：broadcasts，邮件群发）
 */
class BroadcastMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Broadcast $broadcast, public User $recipient) {}

    public function build(): static
    {
        return $this->subject($this->broadcast->title)
            ->view('emails.broadcast')
            ->with([
                'title' => $this->broadcast->title,
                'content' => $this->broadcast->content,
                'recipient' => $this->recipient,
            ]);
    }
}

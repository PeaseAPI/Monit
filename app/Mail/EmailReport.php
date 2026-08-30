<?php

namespace App\Mail;

use App\Models\Website;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 邮件报表（规格书 §13.1：email_reports，每周发送）
 */
class EmailReport extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Website $website, public array $stats) {}

    public function build(): static
    {
        return $this->subject(__('msg.email_report_subject', ['name' => $this->website->name]))
            ->view('emails.email-report')
            ->with([
                'website' => $this->website,
                'stats' => $this->stats,
            ]);
    }
}

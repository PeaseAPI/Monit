<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * 联系表单邮件（规格书 §6.1：/contact）
 */
class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] '.__('contact.title').' — '.$this->payload['name'],
            replyTo: [$this->payload['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact',
            with: ['payload' => $this->payload],
        );
    }
}

<?php

namespace App\Mail;

use App\Support\Typed;
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

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.Typed::string(config('app.name')).'] '.__('contact.title').' — '.Typed::string($this->payload['name'] ?? ''),
            replyTo: [Typed::string($this->payload['email'] ?? '')],
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

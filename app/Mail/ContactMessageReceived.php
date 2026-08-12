<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New enquiry: '.Str::limit($this->contactMessage->subject, 60),
            // Reply-To, never From: sending as the visitor's address would fail
            // SPF and land the whole site's mail in spam folders.
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-message',
            with: ['inboxUrl' => route('admin.messages.show', $this->contactMessage)],
        );
    }
}

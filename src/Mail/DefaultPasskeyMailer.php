<?php

namespace SchenkeIo\LaravelAuthRouter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SchenkeIo\LaravelAuthRouter\Contracts\PasskeyMailerInterface;

class DefaultPasskeyMailer extends Mailable implements PasskeyMailerInterface
{
    use Queueable, SerializesModels;

    public function __construct(public string $code = '') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Passkey Login Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "Your login code is: {$this->code}",
        );
    }

    public function sendMail(string $code): void
    {
        $this->code = $code;
        // In a real scenario, this might call Mail::send(),
        // but for a default/testing implementation, we keep it simple.
    }
}

<?php

namespace App\Mail;

use App\Models\AltUser as User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AltUserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $password;

    public ?string $token;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password, ?string $token = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->token = $token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido a O.A. Distribuciones',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.alt-user-welcome',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\AltOrder;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;

    public $is_alt;

    /**
     * Create a new message instance.
     */
    public function __construct($order, $is_alt = false)
    {
        $this->is_alt = $is_alt;
        if ($is_alt) {
            $this->order = AltOrder::with(['user', 'items'])->find($order);
        } else {
            $this->order = Order::with(['user', 'items'])->find($order);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->is_alt ? '[ALT] ' : '').'Confirmación de Pedido',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-mail',
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

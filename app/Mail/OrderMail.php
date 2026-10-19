<?php

namespace App\Mail;

use App\Helpers\SettingsHelper;
use App\Models\AltOrder;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     * Esta protección evita el efecto observed: una cola de trabajos (jobs)
     * acumulada durante días se drena a la vez cuando un worker arranca,
     * reenviando confirmaciones de pedidos viejos y agotando la cuota SMTP.
     */
    public function send($mailer)
    {
        if ($this->isStale()) {
            Log::info('OrderMail omitido: el pedido '.($this->is_alt ? '[ALT] ' : '').
                ($this->order?->id).' ('.($this->order?->created_at ?? 'sin fecha').
                ') supera la antigüedad máxima ('.SettingsHelper::settings('order_mail_stale_days', 3).' días); no se envía su confirmación.');

            return null;
        }

        return parent::send($mailer);
    }

    /**
     * El pedido supera la antigüedad máxima configurada (`order_mail_stale_days`),
     * por lo que su confirmación ya no aporta información.
     */
    public function isStale(?int $staleDays = null): bool
    {
        $staleDays ??= (int) SettingsHelper::settings('order_mail_stale_days', 3);
        if ($staleDays <= 0) {
            return false;
        }

        $createdAt = $this->order?->created_at;

        return $createdAt !== null && $createdAt->lt(now()->subDays($staleDays));
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

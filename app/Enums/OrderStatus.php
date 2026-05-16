<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case ON_HOLD = 'on-hold';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::ON_HOLD => 'En espera',
            self::PROCESSING => 'Procesando',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::FAILED => 'Fallido',
        };
    }
}

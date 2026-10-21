<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case SAVED = 'saved';
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
            self::SAVED => 'Guardado',
            self::ON_HOLD => 'En espera',
            self::PROCESSING => 'Procesando',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
            self::REFUNDED => 'Reembolsado',
            self::FAILED => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLETED => 'success',
            self::PENDING => 'info',
            self::SAVED => 'ghost',
            self::ON_HOLD => 'warning',
            self::CANCELLED, self::FAILED => 'error',
            self::PROCESSING => 'primary',
            default => 'ghost',
        };
    }
}

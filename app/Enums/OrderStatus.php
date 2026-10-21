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

    /**
     * Estados a los que se puede pasar desde el actual.
     *
     * @return array<int, OrderStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SAVED, self::ON_HOLD => [self::PENDING, self::CANCELLED],
            self::PENDING => [self::PROCESSING, self::ON_HOLD, self::COMPLETED, self::CANCELLED],
            self::PROCESSING => [self::COMPLETED, self::CANCELLED, self::ON_HOLD, self::FAILED],
            self::COMPLETED => [self::REFUNDED],
            self::CANCELLED, self::REFUNDED, self::FAILED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
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

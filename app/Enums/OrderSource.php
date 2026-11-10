<?php

namespace App\Enums;

enum OrderSource: string
{
    case WEB = 'web';
    case MOBILE_API = 'mobile_api';

    public function label(): string
    {
        return match ($this) {
            self::WEB => 'Sitio web',
            self::MOBILE_API => 'App móvil',
        };
    }
}

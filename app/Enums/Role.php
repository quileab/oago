<?php

namespace App\Enums;

enum Role: string
{
    case NONE = 'none';
    case GUEST = 'guest';
    case CUSTOMER = 'customer';
    case SALES = 'sales';
    case ADMIN = 'admin';
}
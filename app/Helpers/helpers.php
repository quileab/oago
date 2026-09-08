<?php

use App\Models\AltUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

if (! function_exists('real_user')) {
    function real_user()
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        } elseif (Auth::guard('sanctum')->check()) {
            return Auth::guard('sanctum')->user();
        } elseif (Auth::guard('alt')->check()) {
            return Auth::guard('alt')->user();
        }

        return null;
    }
}

if (! function_exists('current_user')) {
    function current_user()
    {
        $user = real_user();

        if (! $user) {
            return null;
        }

        if ($user->role->value === 'sales') {
            $actingId = session('sales_acting_as_customer_id');
            if ($actingId) {
                return User::find($actingId) ?? $user;
            }
        }

        return $user;
    }
}

if (! function_exists('current_user_cart_id')) {
    function current_user_cart_id()
    {
        $user = current_user();
        if (! $user) {
            return null;
        }

        $prefix = ($user instanceof AltUser) ? 'alt' : 'web';

        return "{$prefix}_{$user->id}";
    }
}

<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

if (!function_exists('current_user')) {
    function current_user() {
        $user = null;
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        } elseif (Auth::guard('alt')->check()) {
            $user = Auth::guard('alt')->user();
        }
        
        if (!$user) return null;

        if ($user->role->value === 'sales') {
            $actingId = session('sales_acting_as_customer_id');
            if ($actingId) {
                return User::find($actingId) ?? $user;
            }
        }

        return $user;
    }
}

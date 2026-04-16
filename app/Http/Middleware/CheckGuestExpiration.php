<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Helpers\SettingsHelper;
use App\Enums\Role;

class CheckGuestExpiration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Si no hay usuario o no es un invitado, continuamos
        if (!$user || $user->role !== Role::GUEST) {
            return $next($request);
        }

        $expirationDays = SettingsHelper::settings('guest_access_ttl_days', 10);
        $expirationDate = $user->created_at->addDays($expirationDays);

        if (now()->isAfter($expirationDate)) {
            // Determinar qué guard cerrar
            if (Auth::guard('alt')->check()) {
                Auth::guard('alt')->logout();
            } else {
                Auth::logout();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Su período de invitado ha caducado.');
        }

        return $next($request);
    }
}

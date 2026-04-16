<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SecurityService
{
    /**
     * Comprueba si una IP es privada o reservada.
     */
    public static function isPrivateIp(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /**
     * Valida si un host está permitido basándose en una whitelist opcional.
     */
    public static function isAllowedHost(string $url, array $allowedHosts = []): bool
    {
        if (empty($allowedHosts)) {
            return true; // Si no hay whitelist, permitimos todos (protegido por SSRF check)
        }

        $host = parse_url($url, PHP_URL_HOST);
        
        foreach ($allowedHosts as $allowed) {
            // Soporte para comodines simples como *.ejemplo.com
            if (str_contains($allowed, '*')) {
                $pattern = '/^' . str_replace(['.', '*'], ['\.', '.*'], $allowed) . '$/i';
                if (preg_match($pattern, $host)) {
                    return true;
                }
            } elseif (strcasecmp($host, $allowed) === 0) {
                return true;
            }
        }

        Log::warning("SecurityService: Host no permitido intentó acceder al proxy: " . $host);
        return false;
    }
}

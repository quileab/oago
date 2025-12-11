<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;

class ImageProxyController extends Controller
{
    public function show(Request $request)
    {
        $remoteUrl = $request->query('url');
        $fallback = public_path('imgs/oago.webp');

        // Validar que la URL sea válida
        if (!filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
            return response()->file($fallback);
        }

        // Validar esquema (solo http y https)
        $scheme = parse_url($remoteUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
             return response()->file($fallback);
        }

        // Validar que no sea una IP privada o local (SSRF)
        $host = parse_url($remoteUrl, PHP_URL_HOST);
        $ips = gethostbynamel($host);

        if ($ips) {
            foreach ($ips as $ip) {
                if ($this->isPrivateIp($ip)) {
                    return response()->file($fallback);
                }
            }
        } else {
             // Si no se puede resolver el host, también fallamos
             return response()->file($fallback);
        }

        try {
            $response = Http::timeout(5)->get($remoteUrl);

            if ($response->successful()) {
                $headers = [
                    'Content-Type' => $response->header('Content-Type') ?? 'image/jpeg',
                    'Cache-Control' => 'public, max-age=86400', // 1 día
                ];

                // Reenviar ETag si existe
                if ($response->header('ETag')) {
                    $headers['ETag'] = $response->header('ETag');
                }

                // Reenviar Last-Modified si existe
                if ($response->header('Last-Modified')) {
                    $headers['Last-Modified'] = $response->header('Last-Modified');
                }

                return Response::make($response->body(), 200, $headers);
            }
        } catch (\Exception $e) {
            // log($e->getMessage());
        }

        // Si no se pudo obtener la imagen remota, servir imagen por defecto
        return response()->file($fallback);
    }

    /**
     * Comprueba si una IP es privada o reservada.
     */
    private function isPrivateIp($ip)
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}

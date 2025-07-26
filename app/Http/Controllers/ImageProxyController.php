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
}

<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Services\SecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageProxyController extends Controller
{
    public function show(Request $request)
    {
        $remoteUrl = $request->query('url');
        $fallback = public_path('imgs/fallback.webp');
        $headers = [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=86400',
        ];

        // Función para servir archivos locales de forma robusta
        $serveLocal = function ($path) use ($headers) {
            if (! file_exists($path)) {
                Log::error('ImageProxy: File not found at '.$path);

                return response('', 404);
            }
            try {
                $content = file_get_contents($path);

                return response($content, 200, array_merge($headers, [
                    'Content-Length' => strlen($content),
                ]));
            } catch (\Exception $e) {
                Log::error("ImageProxy: Error reading file at $path: ".$e->getMessage());

                return response('', 500);
            }
        };

        // 1. Validar URL vacía o malformada
        if (empty($remoteUrl) || ! filter_var($remoteUrl, FILTER_VALIDATE_URL)) {
            return $serveLocal($fallback);
        }

        // 2. Validar esquema
        $scheme = parse_url($remoteUrl, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'])) {
            return $serveLocal($fallback);
        }

        // 3. Whitelist Check
        $allowedHosts = SettingsHelper::settings('image_proxy_allowed_hosts', []);
        if (! SecurityService::isAllowedHost($remoteUrl, $allowedHosts)) {
            return $serveLocal($fallback);
        }

        // 4. SSRF Protection (DNS Resolve)
        $host = parse_url($remoteUrl, PHP_URL_HOST);
        $ips = @gethostbynamel($host);
        if ($ips) {
            foreach ($ips as $ip) {
                if (SecurityService::isPrivateIp($ip)) {
                    Log::warning('ImageProxy: Intento de SSRF detectado para IP privada: '.$ip);

                    return $serveLocal($fallback);
                }
            }
        } else {
            return $serveLocal($fallback);
        }

        // 5. Lógica de Caché
        $cacheKey = md5($remoteUrl);
        $part1 = substr($cacheKey, 0, 2);
        $part2 = substr($cacheKey, 2, 2);
        $cacheDir = "image_cache/{$part1}/{$part2}";
        $cacheFile = "{$cacheDir}/{$cacheKey}.webp";

        if (Storage::disk('public')->exists($cacheFile)) {
            return $serveLocal(Storage::disk('public')->path($cacheFile));
        }

        // 6. Descarga y conversión
        try {
            $response = Http::timeout(5)->get($remoteUrl);

            if ($response->successful()) {
                $imageContent = $response->body();

                if (empty($imageContent)) {
                    return $serveLocal($fallback);
                }

                // Intentar conversión a WEBP si GD está disponible
                try {
                    if (function_exists('imagewebp')) {
                        $image = @imagecreatefromstring($imageContent);

                        if ($image !== false) {
                            if (! Storage::disk('public')->exists($cacheDir)) {
                                Storage::disk('public')->makeDirectory($cacheDir);
                            }

                            imagepalettetotruecolor($image);
                            imagealphablending($image, true);
                            imagesavealpha($image, true);

                            ob_start();
                            imagewebp($image, null, 80);
                            $webpData = ob_get_clean();
                            imagedestroy($image);

                            if ($webpData) {
                                Storage::disk('public')->put($cacheFile, $webpData);

                                return response($webpData, 200, array_merge($headers, [
                                    'Content-Length' => strlen($webpData),
                                ]));
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('ImageProxy conversion error: '.$e->getMessage());
                }

                // Si falló la conversión o no hay soporte, servir el contenido original
                $contentType = $response->header('Content-Type') ?? 'image/jpeg';

                return response($imageContent, 200, [
                    'Content-Type' => $contentType,
                    'Cache-Control' => 'public, max-age=86400',
                    'Content-Length' => strlen($imageContent),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ImageProxy download error: '.$e->getMessage());
        }

        // Si nada funcionó, fallback final
        return $serveLocal($fallback);
    }
}

@props(['url' => null, 'fallback' => null])

@php
    $fallbackUrl = $fallback ?? asset('imgs/fallback.webp');
    $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
    $requestHost = request()->getHost();
    $urlHost = $url ? parse_url($url, PHP_URL_HOST) : null;
    
    $isExternal = $url && str_starts_with($url, 'http') && 
                  $urlHost && 
                  $urlHost !== $currentHost && 
                  $urlHost !== $requestHost &&
                  $urlHost !== 'localhost' && 
                  $urlHost !== '127.0.0.1';
                  
    $displayUrl = $url ? ($isExternal ? route('proxy.image', ['url' => $url]) : $url) : $fallbackUrl;

    // Check if local file exists to prevent 404 requests in browser
    if (!$isExternal && $url) {
        $parsedUrl = parse_url($url, PHP_URL_PATH);
        if ($parsedUrl) {
            $path = ltrim($parsedUrl, '/');
            $exists = false;

            // Primero verificar si pertenece al disco público de Storage
            if (str_starts_with($path, 'storage/')) {
                $storagePath = substr($path, strlen('storage/'));
                $exists = \Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath);
            }

            // Si no o no se encontró, verificar con public_path
            if (!$exists) {
                $localPath = public_path($path);
                $exists = file_exists($localPath);
            }

            if (!$exists) {
                $displayUrl = $fallbackUrl;
            }
        }
    }
@endphp

<img src="{{ $displayUrl }}" 
     onerror="this.onerror=null; this.src='{{ $fallbackUrl }}';" 
     {{ $attributes->merge(['loading' => 'lazy', 'decoding' => 'async']) }}>
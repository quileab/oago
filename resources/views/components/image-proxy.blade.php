@php
    $fallbackUrl = asset('imgs/fallback.webp');
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
            $localPath = public_path(ltrim($parsedUrl, '/'));
            if (!file_exists($localPath)) {
                $displayUrl = $fallbackUrl;
            }
        }
    }
@endphp

<img src="{{ $displayUrl }}" 
     onerror="this.onerror=null; this.src='{{ $fallbackUrl }}';" 
     {{ $attributes->merge(['loading' => 'lazy']) }}>
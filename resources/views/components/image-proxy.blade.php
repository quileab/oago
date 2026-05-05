@php
    $fallbackUrl = '/imgs/fallback.webp';
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
@endphp

<img src="{{ $displayUrl }}" 
     onerror="if (this.src !== '{{ $fallbackUrl }}') this.src='{{ $fallbackUrl }}';" 
     {{ $attributes->merge(['loading' => 'lazy']) }}>
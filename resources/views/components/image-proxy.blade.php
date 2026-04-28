@php
    $fallbackUrl = '/imgs/fallback.webp';
    $isExternal = $url && str_starts_with($url, 'http') && !str_contains($url, config('app.url')) && !str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1');
    $displayUrl = $url ? ($isExternal ? route('proxy.image', ['url' => $url]) : $url) : $fallbackUrl;
@endphp

<img src="{{ $displayUrl }}" 
     onerror="if (this.src !== '{{ $fallbackUrl }}') this.src='{{ $fallbackUrl }}';" 
     {{ $attributes->merge(['loading' => 'lazy']) }}>
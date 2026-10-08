@php
    $faviconTheme = config('app.theme', 'default');
    $faviconBase = 'themes/' . $faviconTheme . '/images/favicon';
    $faviconAvailable = $faviconTheme !== 'default'
        && file_exists(public_path($faviconBase . '-32.png'));
@endphp

@if($faviconAvailable)
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset($faviconBase . '-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset($faviconBase . '-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset($faviconBase . '-180.png') }}">
@else
    <link rel="icon" type="image/webp" href="{{ asset('imgs/fallback.webp') }}">
@endif

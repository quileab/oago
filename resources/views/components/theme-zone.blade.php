@props(['name' => ''])

@php
    $zoneName = trim((string) $name);
    $resolvedZoneView = null;

    if ($zoneName !== '' && preg_match('/^[a-z0-9_]+$/i', $zoneName)) {
        $activeTheme = (string) config('app.theme', 'default');
        $activeVariant = (string) config('app.theme_variant', '');

        $candidates = [];
        if ($activeTheme !== 'default' && $activeTheme !== '') {
            if ($activeVariant !== '') {
                $candidates[] = "themes.{$activeTheme}.{$activeVariant}.partials.zones.{$zoneName}";
            }
            $candidates[] = "themes.{$activeTheme}.partials.zones.{$zoneName}";
        } else {
            $candidates[] = "partials.zones.{$zoneName}";
        }

        foreach ($candidates as $candidate) {
            if (view()->exists($candidate)) {
                $resolvedZoneView = $candidate;
                break;
            }
        }
    }
@endphp

@if ($resolvedZoneView !== null)
    @include($resolvedZoneView)
@endif

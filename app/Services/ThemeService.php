<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class ThemeService
{
    private const MISSING_SENTINEL = '__MISSING_THEME_SETTING__';

    /**
     * Resolve the currently active theme name from database settings with config fallback.
     */
    public function activeTheme(): string
    {
        $fallback = (string) (config('app.theme', 'default') ?: 'default');

        try {
            $stored = SettingsHelper::settings('active_theme', self::MISSING_SENTINEL);
            if ($stored !== self::MISSING_SENTINEL && is_string($stored) && trim($stored) !== '') {
                return trim($stored);
            }
        } catch (\Throwable) {
            return $fallback;
        }

        return $fallback;
    }

    /**
     * Resolve the currently active theme variant from database settings with config fallback.
     */
    public function activeVariant(): string
    {
        $fallback = (string) (config('app.theme_variant', '') ?: '');

        try {
            $stored = SettingsHelper::settings('active_theme_variant', self::MISSING_SENTINEL);
            if ($stored !== self::MISSING_SENTINEL && is_string($stored)) {
                return trim($stored);
            }
        } catch (\Throwable) {
            return $fallback;
        }

        return $fallback;
    }

    /**
     * Synchronize the runtime config keys (`app.theme` and `app.theme_variant`) from DB settings.
     */
    public function syncRuntimeConfig(): void
    {
        config([
            'app.theme' => $this->activeTheme(),
            'app.theme_variant' => $this->activeVariant() ?: null,
        ]);
    }

    /**
     * Persist the active theme and optional variant in the settings table and clear view cache.
     */
    public function activateTheme(string $theme, string $variant = ''): void
    {
        $theme = trim($theme);
        $variant = trim($variant);

        if (! $this->isValidSegment($theme)) {
            throw new InvalidArgumentException('El nombre del tema contiene caracteres no permitidos.');
        }

        if ($variant !== '' && ! $this->isValidSegment($variant)) {
            throw new InvalidArgumentException('El nombre de la variante contiene caracteres no permitidos.');
        }

        $defaults = config('default_settings', []);

        foreach (['active_theme' => $theme, 'active_theme_variant' => $variant] as $key => $value) {
            $meta = $defaults[$key] ?? [];
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $meta['type'] ?? 'string',
                    'text' => $meta['text'] ?? $key,
                    'description' => $meta['description'] ?? null,
                ]
            );
        }

        SettingsHelper::clearCache('active_theme');
        SettingsHelper::clearCache('active_theme_variant');

        config([
            'app.theme' => $theme,
            'app.theme_variant' => $variant ?: null,
        ]);

        try {
            Artisan::call('view:clear');
        } catch (\Throwable) {
            // Silently ignore if running in a context without view cache driver
        }
    }

    /**
     * Check whether a theme, variant, or slug segment contains only safe path characters.
     */
    public function isValidSegment(string $segment): bool
    {
        return $segment !== '' && (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $segment);
    }

    /**
     * Build the view relative prefix for a theme and optional variant.
     */
    public function resolvePrefix(string $theme, ?string $variant = null): string
    {
        $theme = trim($theme);
        $variant = trim((string) $variant);

        return $variant !== '' ? "{$theme}/{$variant}" : $theme;
    }
}

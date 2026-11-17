<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Per-request in-memory cache to avoid redundant cache store lookups.
     *
     * @var array<string, mixed>
     */
    private static array $memo = [];

    /**
     * Get a setting value from the database, using cache for performance.
     *
     * @param  string  $key  The key of the setting.
     * @param  mixed  $default  The default value to return if the setting is not found.
     * @return mixed
     */
    public static function settings($key, $default = null)
    {
        if (array_key_exists($key, self::$memo)) {
            return self::$memo[$key];
        }

        return self::$memo[$key] = Cache::rememberForever('settings.'.$key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            switch ($setting->type) {
                case 'number':
                    return (float) $setting->value;
                case 'boolean':
                    return (bool) $setting->value;
                case 'json':
                    $decoded = json_decode($setting->value, true);

                    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $default;
                case 'string':
                default:
                    return $setting->value;
            }
        });
    }

    /**
     * Create or update a setting value and clear its cache.
     *
     * @param  string  $key  The key of the setting.
     * @param  mixed  $value  The value to store.
     * @return void
     */
    public static function update_setting($key, $value)
    {
        // If the value is an array, encode it to JSON before saving
        if (is_array($value)) {
            $value = json_encode($value);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        unset(self::$memo[$key]);
        Cache::forget('settings.'.$key);
    }

    /**
     * Clear cached setting value(s).
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            unset(self::$memo[$key]);
            Cache::forget('settings.'.$key);

            return;
        }

        self::flushMemo();

        foreach (['active_theme', 'active_theme_variant'] as $themeKey) {
            Cache::forget('settings.'.$themeKey);
        }
    }

    /**
     * Drop the in-memory memo without touching the shared cache store.
     *
     * The memo is static, so it survives across app instances (test suite, queue workers
     * running several jobs in one process). Reset it whenever the database state changes
     * underneath the process.
     */
    public static function flushMemo(): void
    {
        self::$memo = [];
    }
}

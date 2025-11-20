<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Get a setting value from the database, using cache for performance.
     *
     * @param string $key The key of the setting.
     * @param mixed $default The default value to return if the setting is not found.
     * @return mixed
     */
    public static function settings($key, $default = null)
    {
        return Cache::rememberForever('settings.' . $key, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) {
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
     * @param string $key The key of the setting.
     * @param mixed $value The value to store.
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

        Cache::forget('settings.' . $key);
    }
}

<?php

namespace App\Models;

use App\Helpers\SettingsHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'text',
        'description',
    ];

    protected static function booted(): void
    {
        static::saved(function (Setting $setting): void {
            SettingsHelper::clearCache($setting->key);
        });

        static::deleted(function (Setting $setting): void {
            SettingsHelper::clearCache($setting->key);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function listPrices()
    {
        return $this->hasMany(ListPrice::class); // Un producto puede tener múltiples precios en diferentes listas
    }

    public static function getTags()
    {
        return \App\Helpers\SettingsHelper::getProductTags();
    }

    public function hasBonus(): bool
    {
        return $this->bonus_threshold > 0 && $this->bonus_amount > 0;
    }

    public function getBonusLabelAttribute(): string
    {
        if (!$this->hasBonus()) {
            return '';
        }
        return "{$this->bonus_threshold} + {$this->bonus_amount} off !!";
    }
}

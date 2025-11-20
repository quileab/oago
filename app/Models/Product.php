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
        return ['NUEVO', 'OFERTA', 'REMATE', 'IMPORTADOS'];
    }

    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $product_id
 * @property int $list_id
 * @property float $price
 * @property float|null $unit_price
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ListName $list
 */
class ListPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'list_id', 'price', 'unit_price'];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class); // Este precio pertenece a un producto específico
    }

    public function list(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ListName::class, 'list_id'); // Este precio pertenece a una lista de precios específica
    }
}

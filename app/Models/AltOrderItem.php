<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AltOrderItem extends Model
{
    protected $table = 'alt_order_items';

    protected $fillable = [
        'alt_order_id',
        'product_id',
        'quantity',
        'price',
    ];

    public function order()
    {
        return $this->belongsTo(AltOrder::class, 'alt_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

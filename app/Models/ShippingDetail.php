<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingDetail extends Model
{
    protected $fillable = [
        'order_id',
        'alt_order_id',
        'contact_name',
        'address',
        'city',
        'postal_code',
        'phone',
        'shipping_status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function altOrder()
    {
        return $this->belongsTo(AltOrder::class, 'alt_order_id');
    }
}

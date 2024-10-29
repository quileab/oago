<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //protected $fillable = ['status'];
    protected $guarded = [];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    public function shipping()
    {
        return $this->hasOne(ShippingDetail::class);
    } 
 
}

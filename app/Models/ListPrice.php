<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'list_id', 'price', 'unit_price'];

    public function product()
    {
        return $this->belongsTo(Product::class); // Este precio pertenece a un producto específico
    }

    public function list()
    {
        return $this->belongsTo(ListName::class); // Este precio pertenece a una lista de precios específica
    }
}

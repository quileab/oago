<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListName extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class); // Una lista puede tener muchos usuarios
    }

    public function listPrices()
    {
        return $this->hasMany(ListPrice::class); // Una lista puede tener muchos precios asociados a diferentes productos
    }
}

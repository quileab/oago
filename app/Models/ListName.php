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
        return $this->hasMany(User::class, 'list_id'); // Una lista puede tener muchos usuarios
    }

    public function altUsers()
    {
        return $this->hasMany(AltUser::class, 'list_id'); // Una lista puede tener muchos usuarios alternativos
    }

    public function listPrices()
    {
        return $this->hasMany(ListPrice::class, 'list_id'); // Una lista puede tener muchos precios asociados a diferentes productos
    }
}

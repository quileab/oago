<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
     protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // create tokens for the user
    // public function createToken(string $name, array $abilities = []): Token
    // {
    //     return $this->tokens()->create([
    //         'name' => $name,
    //         'abilities' => $abilities,
    //     ]);
    // }

    public function list()
    {
        return $this->belongsTo(ListName::class); // Un usuario pertenece a una lista de precios
    }   
    
    public function getProductPrice(Product $product)
    {
        return $this->list->listPrices()->where('product_id', $product->id)->first()->price ?? null;
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getFullNameAttribute()
    {
        if($this->lastname && $this->name){
            return $this->lastname . ', ' . $this->name;
        }

        return '✨SYS: ' . $this->name;
    }

}

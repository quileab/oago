<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Models\Achievement;
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
    protected $fillable = [
        'name',
        'lastname',
        'address',
        'city',
        'postal_code',
        'phone',
        'email',
        'password',
        'list_id',
        'is_internal',
    ];

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
            'role' => Role::class,
            'is_internal' => 'boolean',
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

    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    public function getFullNameAttribute()
    {
        if ($this->lastname && $this->name) {
            return $this->lastname . ', ' . $this->name;
        }

        return '✨SYS: ' . $this->name;
    }

    public function achievements()
    {
        return $this->morphToMany(Achievement::class, 'achievable');
    }

    public function getTotalPointsAttribute()
    {
        return $this->achievements()->where('type', 'points')->get()->sum('data.amount');
    }

    public function assignedSalesAgents()
    {
        return $this->hasMany(CustomerSalesAgent::class, 'customer_id');
    }

    public function assignedCustomers()
    {
        return $this->morphMany(CustomerSalesAgent::class, 'sales_agent');
    }

    /**
     * Get the query for customers managed by this user.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getManagedCustomersQuery()
    {
        if ($this->is_internal || $this->role === Role::ADMIN) {
            return User::where('role', Role::CUSTOMER);
        }

        return User::whereHas('assignedSalesAgents', function ($query) {
            $query->where('sales_agent_id', $this->id)
                  ->where('sales_agent_type', self::class);
        });
    }

}

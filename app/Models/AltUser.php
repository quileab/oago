<?php

namespace App\Models;

use App\Enums\Role;
use App\Traits\HasAchievements;
use App\Traits\HasPricingList;
use App\Traits\HasProfileData;
use App\Traits\ManagesCustomers;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AltUser extends Authenticatable
{
    use HasAchievements, HasPricingList, HasProfileData, ManagesCustomers;
    use Notifiable;

    protected $table = 'alt_users';

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
        'role',
        'remember_token',
        'activation_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'is_internal' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(AltOrder::class, 'alt_user_id');
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(AltOrder::class, 'alt_user_id')->latestOfMany();
    }
}

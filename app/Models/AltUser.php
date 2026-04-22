<?php

namespace App\Models;

use App\Enums\Role;
use App\Traits\HasAchievements;
use App\Traits\HasPricingList;
use App\Traits\HasProfileData;
use App\Traits\ManagesCustomers;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AltUser extends Authenticatable
{
    use HasAchievements, HasPricingList, HasProfileData, ManagesCustomers;
    use Notifiable;

    protected $table = 'alt_users';

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

    protected $hidden = [
        'password',
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
}

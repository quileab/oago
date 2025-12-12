<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AltUser extends Authenticatable
{
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
            'role' => Role::class,
            'is_internal' => 'boolean',
        ];
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

    public function assignedCustomers()
    {
        return $this->morphMany(CustomerSalesAgent::class, 'sales_agent');
    }

    /**
     * Get the query for customers managed by this alt user.
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

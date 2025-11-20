<?php

namespace App\Models;

use App\Models\User;
use App\Models\AltUser;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function users()
    {
        return $this->morphedByMany(User::class, 'achievable');
    }

    public function altUsers()
    {
        return $this->morphedByMany(AltUser::class, 'achievable');
    }
}

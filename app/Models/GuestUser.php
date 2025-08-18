<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GuestUser extends Authenticatable
{
    use Notifiable;
    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    public function getFullNameAttribute()
    {
        if ($this->lastname && $this->name) {
            return $this->lastname . ', ' . $this->name;
        }

        return '✨SYS: ' . $this->name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class GuestUser extends Authenticatable
{
    use Notifiable;
    protected $guarded = ['id', 'created_at', 'updated_at'];
}

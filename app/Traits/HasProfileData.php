<?php

namespace App\Traits;

trait HasProfileData
{
    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->lastname && $this->name) {
            return $this->lastname.', '.$this->name;
        }

        return '✨SYS: '.$this->name;
    }

    /**
     * Common profile fields for fillable.
     */
    public static function getCommonFillable(): array
    {
        return [
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
        ];
    }
}

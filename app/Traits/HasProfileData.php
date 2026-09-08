<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

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
     * Normalize the user's name to Title Case.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? Str::title(trim($value)) : null,
        );
    }

    /**
     * Normalize the user's lastname to Title Case.
     */
    protected function lastname(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value ? Str::title(trim($value)) : null,
        );
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

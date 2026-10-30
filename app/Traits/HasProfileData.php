<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
     *
     * @return array<int, string>
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

    /**
     * Common validation rules for profile fields matching the database column lengths.
     *
     * @return array<string, mixed>
     */
    public static function getProfileRules(string $table, ?int $ignoreId = null, string $prefix = ''): array
    {
        return [
            "{$prefix}name" => 'required|string|max:30',
            "{$prefix}lastname" => 'required|string|max:30',
            "{$prefix}address" => 'required|string|max:100',
            "{$prefix}city" => 'required|string|max:30',
            "{$prefix}postal_code" => 'required|string|max:10',
            "{$prefix}phone" => 'required|string|max:50',
            "{$prefix}email" => [
                'required',
                'email',
                'max:255',
                Rule::unique($table, 'email')->ignore($ignoreId),
            ],
        ];
    }

    /**
     * Common Spanish validation messages for profile fields.
     *
     * @return array<string, string>
     */
    public static function getProfileMessages(string $prefix = ''): array
    {
        return [
            "{$prefix}name.required" => 'El nombre es requerido.',
            "{$prefix}name.max" => 'El nombre no puede superar los 30 caracteres.',
            "{$prefix}lastname.required" => 'El apellido es requerido.',
            "{$prefix}lastname.max" => 'El apellido no puede superar los 30 caracteres.',
            "{$prefix}address.required" => 'La dirección es requerida.',
            "{$prefix}address.max" => 'La dirección no puede superar los 100 caracteres.',
            "{$prefix}city.required" => 'La ciudad es requerida.',
            "{$prefix}city.max" => 'La ciudad no puede superar los 30 caracteres.',
            "{$prefix}postal_code.required" => 'El código postal es requerido.',
            "{$prefix}postal_code.max" => 'El código postal no puede superar los 10 caracteres.',
            "{$prefix}phone.required" => 'El teléfono es requerido.',
            "{$prefix}phone.max" => 'El teléfono no puede superar los 50 caracteres.',
            "{$prefix}email.required" => 'El e-mail es requerido.',
            "{$prefix}email.email" => 'El e-mail no es válido.',
            "{$prefix}email.unique" => 'El e-mail ya existe.',
        ];
    }
}

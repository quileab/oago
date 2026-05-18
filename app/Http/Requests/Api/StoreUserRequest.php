<?php

namespace App\Http\Requests\Api;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check(); // Basic check, specific role checks are in Controller or Policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $existingUser = User::find($this->id);

        $rules = [
            'id' => ['required', 'integer'],
            'name' => 'required|string|max:30',
            'lastname' => 'required|string|max:30',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($existingUser?->id)],
            'password' => 'nullable|string|min:3',
        ];

        if (!$existingUser) {
            $rules['id'][] = 'unique:users,id';
        }

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['required', 'string', Rule::in(array_map(fn ($role) => $role->value, Role::cases()))];
            $rules['list_id'] = 'nullable|exists:list_names,id';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (auth()->user()->role !== Role::ADMIN) {
            $this->merge([
                'role' => Role::CUSTOMER->value,
                'list_id' => null,
            ]);
        }
    }
}

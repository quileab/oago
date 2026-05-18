<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('role') && auth()->user()->role === Role::ADMIN) {
            $query->where('role', $request->role);
        }

        if (auth()->user()->role !== Role::ADMIN) {
            $query->where('id', auth()->id());
        }

        $users = $query->get();

        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage or update an existing one.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $existingUser = User::find($validatedData['id']);

        $warnings = [];
        $defaults = [
            'address' => 'S/D',
            'city' => 'S/D',
            'postal_code' => '0000',
            'phone' => '+54',
        ];

        foreach ($defaults as $field => $defaultValue) {
            if (empty($validatedData[$field])) {
                $validatedData[$field] = $defaultValue;
                $warnings[] = "El campo '{$field}' estaba vacío y se asignó el valor por defecto: '{$defaultValue}'.";
            }
        }

        $userData = [
            'name' => $validatedData['name'],
            'lastname' => $validatedData['lastname'],
            'address' => $validatedData['address'],
            'city' => $validatedData['city'],
            'postal_code' => $validatedData['postal_code'],
            'phone' => $validatedData['phone'],
            'email' => $validatedData['email'],
            'role' => $validatedData['role'] ?? Role::CUSTOMER->value,
            'list_id' => $validatedData['list_id'] ?? null,
        ];

        if (! empty($validatedData['password'])) {
            $userData['password'] = Hash::make($validatedData['password']);
        } elseif (! $existingUser) {
            $userData['password'] = Hash::make((string) $validatedData['id']);
        }

        $user = User::updateOrCreate(
            ['id' => $validatedData['id']],
            $userData
        );

        $status = $existingUser ? 200 : 201;
        $message = $existingUser ? 'Usuario actualizado exitosamente.' : 'Usuario creado exitosamente.';

        return response()->json([
            'user' => $user,
            'warnings' => $warnings,
            'message' => $message,
        ], $status);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id && auth()->user()->role !== Role::ADMIN) {
            abort(403, 'Unauthorized to view this user profile.');
        }

        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        if (auth()->id() !== $user->id && auth()->user()->role !== Role::ADMIN) {
            abort(403, 'Unauthorized to update this user profile.');
        }

        $rules = [
            'name' => 'string|max:30',
            'lastname' => 'string|max:30',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => ['email', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:3',
        ];

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['string', \Illuminate\Validation\Rule::in(array_map(fn ($role) => $role->value, Role::cases()))];
            $rules['list_id'] = 'nullable|exists:list_names,id';
        }

        $validatedData = $request->validate($rules);

        $warnings = [];
        $defaults = [
            'address' => 'S/D',
            'city' => 'S/D',
            'postal_code' => '0000',
            'phone' => '+54',
        ];

        foreach ($defaults as $field => $defaultValue) {
            if (array_key_exists($field, $validatedData) && empty($validatedData[$field])) {
                $validatedData[$field] = $defaultValue;
                $warnings[] = "El campo '{$field}' fue enviado vacío y se asignó el valor por defecto: '{$defaultValue}'.";
            }
        }

        $dataToUpdate = array_intersect_key($validatedData, array_flip([
            'name', 'lastname', 'address', 'city', 'postal_code', 'phone', 'email', 'list_id', 'role'
        ]));

        if (isset($validatedData['password'])) {
            $dataToUpdate['password'] = Hash::make($validatedData['password']);
        }

        $user->update($dataToUpdate);

        return response()->json([
            'user' => $user,
            'warnings' => $warnings,
            'message' => 'Usuario actualizado correctamente.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id && auth()->user()->role !== Role::ADMIN) {
            abort(403, 'Unauthorized to delete this user profile.');
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}

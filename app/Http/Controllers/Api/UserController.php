<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Enums\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * @response array{data: Users[]}
     */
    public function index(Request $request)
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
    public function store(Request $request)
    {
        // Buscamos si el usuario ya existe por ID
        $existingUser = User::find($request->id);

        $rules = [
            'id' => 'required|integer',
            'name' => 'required|string|max:30',
            'lastname' => 'required|string|max:30',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($existingUser?->id)],
            'password' => 'nullable|string|min:3',
        ];

        // Solo validamos ID único si NO existe el usuario
        if (!$existingUser) {
            $rules['id'] .= '|unique:users,id';
        }

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['required', 'string', Rule::in(array_map(fn($role) => $role->value, Role::cases()))];
            $rules['list_id'] = 'nullable|exists:list_names,id';
        } else {
            $request->merge(['role' => Role::CUSTOMER->value]);
            $request->merge(['list_id' => null]);
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

        // Solo actualizamos el password si se envía uno nuevo
        if (!empty($validatedData['password'])) {
            $userData['password'] = Hash::make($validatedData['password']);
        } elseif (!$existingUser) {
            // Si es un usuario nuevo y no viene password, usamos el ID como password
            $userData['password'] = Hash::make((string)$validatedData['id']);
        }

        // Usamos updateOrCreate para manejar ambos casos
        $user = User::updateOrCreate(
            ['id' => $validatedData['id']],
            $userData
        );

        $status = $existingUser ? 200 : 201;
        $message = $existingUser ? 'Usuario actualizado exitosamente.' : 'Usuario creado exitosamente.';

        return response()->json([
            'user' => $user,
            'warnings' => $warnings,
            'message' => $message
        ], $status);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() !== $user->id && auth()->user()->role !== Role::ADMIN) {
            abort(403, 'Unauthorized to view this user profile.');
        }
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

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
            'email' => ['email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:3',
            'list_id' => 'nullable|exists:list_names,id',
        ];

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['string', Rule::in(array_map(fn($role) => $role->value, Role::cases()))];
        } else {
             $request->offsetUnset('role');
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

        $dataToUpdate = [
            'name' => $validatedData['name'] ?? $user->name,
            'lastname' => $validatedData['lastname'] ?? $user->lastname,
            'address' => $validatedData['address'] ?? $user->address,
            'city' => $validatedData['city'] ?? $user->city,
            'postal_code' => $validatedData['postal_code'] ?? $user->postal_code,
            'phone' => $validatedData['phone'] ?? $user->phone,
            'email' => $validatedData['email'] ?? $user->email,
            'list_id' => $validatedData['list_id'] ?? $user->list_id,
        ];

        if (isset($validatedData['password'])) {
            $dataToUpdate['password'] = Hash::make($validatedData['password']);
        }

        if (auth()->user()->role === Role::ADMIN && isset($validatedData['role'])) {
            $dataToUpdate['role'] = $validatedData['role'];
        }
        
        $user->update($dataToUpdate);
        
        return response()->json([
            'user' => $user,
            'warnings' => $warnings,
            'message' => 'Usuario actualizado correctamente.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() !== $user->id && auth()->user()->role !== Role::ADMIN) {
            abort(403, 'Unauthorized to delete this user profile.');
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}

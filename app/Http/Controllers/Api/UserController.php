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
     * Store a newly created resource in storage.
     * This method assumes user creation is for self-registration or admin.
     */
    public function store(Request $request)
    {
        $request->offsetUnset('id');

        $rules = [
            'name' => 'required|string|max:30',
            'lastname' => 'required|string|max:30',
            'address' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => 'required|string|min:8',
        ];

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['required', 'string', Rule::in(array_map(fn($role) => $role->value, Role::cases()))];
            $rules['list_id'] = 'nullable|exists:list_names,id';
        } else {
            $request->merge(['role' => Role::CUSTOMER->value]);
            $request->merge(['list_id' => null]);
        }

        $validatedData = $request->validate($rules);
        
        $user = User::create([
            'name' => $validatedData['name'],
            'lastname' => $validatedData['lastname'],
            'address' => $validatedData['address'] ?? null,
            'city' => $validatedData['city'] ?? null,
            'postal_code' => $validatedData['postal_code'] ?? null,
            'phone' => $validatedData['phone'] ?? null,
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'] ?? Role::CUSTOMER->value,
            'list_id' => $validatedData['list_id'] ?? null,
        ]);

        return response()->json($user, 201);
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
            'password' => 'nullable|string|min:8',
            'list_id' => 'nullable|exists:list_names,id',
        ];

        if (auth()->user()->role === Role::ADMIN) {
            $rules['role'] = ['string', Rule::in(array_map(fn($role) => $role->value, Role::cases()))];
        } else {
             $request->offsetUnset('role');
        }

        $validatedData = $request->validate($rules);
        
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
        return response()->json($user, 200);
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * @response array{data: Users[]}
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtrar por rol (por ejemplo, "customer" o "admin")
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->get();
        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable',
            'name' => 'required|string|max:30',
            'lastname' => 'required|string|max:30',
            'address' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:15',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,customer,other', // Ajustar según los roles permitidos
            'list_id' => 'nullable|exists:list_names,id', // validar solo si el cliente pertenece a una lista de precios
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'id' => $request->id,
            'name' => $request->name,
            'lastname' => $request->lastname,
            'address' => $request->address,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'list_id' => $request->list_id,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id' => 'exists:users,id',
            'name' => 'string|max:30',
            'lastname' => 'string|max:30',
            'address' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:30',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:15',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'string|in:admin,customer,other',
            'list_id' => 'nullable|exists:list_names,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'lastname', 'address', 'city', 'postal_code', 'phone', 'email', 'role', 'list_id']);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return response()->json($user, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}

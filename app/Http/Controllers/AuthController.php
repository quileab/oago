<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                  'email' => 'required|email|exists:users',
                  'password' => 'required',
            ]);
          }
          catch (Exception $e) {
            return response()->json([
              'message' => $e->getMessage(),
            ], 400);
          }
        $user = User::where('email', $request->email)->first();

        $deniedRoles = ['none', 'other', null, ''];
        if (in_array($user->role, $deniedRoles, true)) {
            $message = $user->role === 'other' ? 'Usuario no definido' : 'Usuario no activo';
            return response()->json(['message' => $message], 401);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        $token = $user->createToken($user->name)->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    
    }

    public function register(Request $request)
    {
    try {
        $fields = $request->validate([
                'name' => 'required|max:30',
                'lastname' => 'required|max:30',
                'address' => 'required|max:50',
                'city' => 'required|max:30',
                'postal_code' => 'required|max:10',
                'phone' => 'required|max:50',
                'email' => 'required|email|unique:users',
                'password' => 'required|confirmed',
        ]);
        }
        catch (Exception $e) {
        return response()->json([
            'message' => $e->getMessage(),
        ], 400);
        }
        $user = User::create($fields);
        $token = $user->createToken($request->name)->plainTextToken;
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logged out successfully.',
        ]);

    }
}

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
                'name' => 'required|max:50',
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

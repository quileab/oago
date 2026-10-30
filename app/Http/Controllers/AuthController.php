<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Helpers\SettingsHelper;
use App\Models\ListName;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }
        $user->loadMissing(['assignedSalesAgents.salesAgent']);
        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);

    }

    /**
     * @return JsonResponse<User>
     */
    public function register(Request $request): JsonResponse
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
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
        $defaultListId = (int) SettingsHelper::settings('alt_user_default_price', 0);
        $defaultList = ($defaultListId && ListName::where('id', $defaultListId)->exists()) ? $defaultListId : null;

        $user = User::create(array_merge($fields, [
            'role' => Role::NONE->value,
            'list_id' => $defaultList,
            'is_internal' => false,
        ]));
        $token = $user->createToken($request->name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);

    }
}

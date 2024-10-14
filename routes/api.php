<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// sanctum middleware protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    //return users list
    Route::get('/users', function (Request $request) {
        return response()->json($request->user());
        return \App\Models\User::all();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});


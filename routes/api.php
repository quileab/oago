<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\OrderController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// sanctum middleware protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    //return users list
    Route::get('/users', function (Request $request) {
        //return response()->json($request->user());
        return \App\Models\User::all();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});
// group protected Order routes 
//Route::middleware('auth:sanctum')->group(function () {
    // Ruta para obtener el listado de pedidos pendientes
    Route::get('/orders/pending', [OrderController::class, 'listPendingOrders']);
    
    // Ruta para actualizar el estado de un pedido
    Route::put('/orders/{order}/status', [OrderController::class, 'updateOrderStatus']);
    
    // Ruta para actualizar los productos de un pedido
    Route::put('/orders/{order}/products', [OrderController::class, 'updateOrderProducts']);
//});


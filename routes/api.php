<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ListPriceController;

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

Route::apiResource('products', ProductController::class);
Route::post('products/{product}/upload-image', [ProductController::class, 'uploadImage']);
// list-prices resource routes
Route::get('list-prices', [ListPriceController::class, 'index']);
Route::post('list-prices', [ListPriceController::class, 'store']);
Route::get('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'show']);
Route::put('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'update']);
Route::delete('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'destroy']);
// users resource routes
Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::get('users/{id}', [UserController::class, 'show']);
Route::put('users/{id}', [UserController::class, 'update']);
Route::delete('users/{id}', [UserController::class, 'destroy']);
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ListPriceController;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Devuelve solo el usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rutas de Pedidos
    Route::get('/orders/pending', [OrderController::class, 'listPendingOrders']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateOrderStatus']);
    Route::put('/orders/{order}/products', [OrderController::class, 'updateOrderProducts']);

    // Rutas de administración de Productos
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::post('products/{product}/upload-image', [ProductController::class, 'uploadImage']);
    Route::put('products/{product}/visibility', [ProductController::class, 'changeVisibility']);

    // Rutas de Listas de Precios
    Route::get('list-prices', [ListPriceController::class, 'index']);
    Route::post('list-prices', [ListPriceController::class, 'store']);
    Route::get('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'show']);
    Route::put('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'update']);
    Route::delete('list-prices/{product_id}/{list_id}', [ListPriceController::class, 'destroy']);

    // Rutas de administración de Usuarios (potencialmente solo para admins)
    // Se recomienda añadir un middleware de administrador aquí, por ejemplo: ->middleware('is.admin')
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
});

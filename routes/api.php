<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (Request $request) {
    return "Funciona";
});

Route::get('/user', function (Request $request) {
    return "Funciona";//$request->user();
})->middleware('auth:sanctum');
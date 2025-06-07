<?php

use Illuminate\Http\Request;
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Welcome to the Route Service']);
});

Route::middleware('api')->group(function () {
    Route::apiResource('routes', RouteController::class);
});

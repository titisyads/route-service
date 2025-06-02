<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;

Route::middleware('api')->group(function () {
    Route::apiResource('routes', RouteController::class);
});

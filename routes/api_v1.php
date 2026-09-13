<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Flota\TractivoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/

Route::get('ping', fn () => response()->json(['ok' => true, 'version' => 'v1']));

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'api.entidad'])->group(function () {
    // Autenticación
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

    // Flota · Tractivos
    Route::get('tractivos', [TractivoController::class, 'index']);
    Route::get('tractivos/{tractivo}', [TractivoController::class, 'show']);
});

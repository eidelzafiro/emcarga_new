<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TractivosController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| NOTA: El middleware de autenticación y las rutas de login se
| implementan en la Fase 4.1 (Auth + perfiles). Mientras tanto
| las rutas quedan abiertas solo para desarrollo local.
|
*/

Route::redirect('/', '/dashboard');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

// Módulo Técnico - Flota
Route::resource('tractivos', TractivosController::class)
    ->only(['index', 'store', 'update', 'destroy']);

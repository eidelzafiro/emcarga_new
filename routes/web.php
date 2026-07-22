<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\TractivosController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect(auth()->check() ? route('dashboard') : route('login')));

// Invitados
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// Autenticados
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('perfil/cambiar-password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('perfil/cambiar-password', [PasswordController::class, 'update'])->name('password.update');

    // Las notificaciones se sirven incluso con password temporal
    Route::get('notificaciones', [NotificationsController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{id}/leer', [NotificationsController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('notificaciones/leer-todas', [NotificationsController::class, 'markAllAsRead'])->name('notificaciones.leer-todas');

    // Requieren contraseña definitiva (no temporal)
    Route::middleware('password.temporal')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Módulo Técnico - Flota
        Route::resource('tractivos', TractivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Administración de usuarios (Fase 4.3)
        Route::resource('usuarios', UserController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['usuarios' => 'user']);
        Route::post('usuarios/{user}/desbloquear', [UserController::class, 'desbloquear'])
            ->name('usuarios.desbloquear');
        Route::post('usuarios/{user}/restablecer-password', [UserController::class, 'restablecerPassword'])
            ->name('usuarios.restablecer');

        // Administración de perfiles (Fase 4.4)
        Route::resource('perfiles', PerfilController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['perfiles' => 'perfil']);
    });
});

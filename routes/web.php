<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AcuerdosController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BateriasController;
use App\Http\Controllers\CajasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiferencialesController;
use App\Http\Controllers\DistanciasController;
use App\Http\Controllers\EnergiaController;
use App\Http\Controllers\GirosController;
use App\Http\Controllers\LubricantesController;
use App\Http\Controllers\LugaresController;
use App\Http\Controllers\MotoresController;
use App\Http\Controllers\NeumaticosController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OtrosAgregadosController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PizarraController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\PrefacturasController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\TallerController;
use App\Http\Controllers\TipoIngresosController;
use App\Http\Controllers\TractivosController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\HistorialMovimientosController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\TiposContratosController;
use App\Http\Controllers\TiposIncidenciasController;
use App\Http\Controllers\TiposPagosAdicionalesController;
use App\Http\Controllers\TiposPenalizacionesController;
use App\Http\Controllers\TiposSistemasPagoController;
use App\Http\Controllers\TiposTasasController;
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

    // API de KPIs (también accesible con password temporal para el dashboard)
    Route::get('api/kpis', [DashboardController::class, 'kpis'])->name('api.kpis');

    // Las notificaciones se sirven incluso con password temporal
    Route::get('notificaciones', [NotificationsController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{id}/leer', [NotificationsController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('notificaciones/leer-todas', [NotificationsController::class, 'markAllAsRead'])->name('notificaciones.leer-todas');

    // API de pizarra (datos en JSON para Echo/fetch)
    Route::get('api/pizarra', [PizarraController::class, 'datos'])->name('api.pizarra');

    // Requieren contraseña definitiva (no temporal)
    Route::middleware('password.temporal')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Pizarra de vehículos en vivo (Fase 4.10)
        Route::get('pizarra', [PizarraController::class, 'index'])->name('pizarra.index');

        // Módulo Técnico - Flota
        Route::resource('tractivos', TractivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('motores', MotoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('cajas', CajasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('diferenciales', DiferencialesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('baterias', BateriasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('neumaticos', NeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('lubricantes', LubricantesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('otros-agregados', OtrosAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('energia', EnergiaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Taller
        Route::resource('taller', TallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Comercial
        Route::resource('clientes', ClientesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('lugares', LugaresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('distancias', DistanciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('acuerdos', AcuerdosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('solicitudes', SolicitudesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('giros', GirosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Facturación (Fase 5.4)
        Route::resource('facturas', FacturasController::class)
            ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::post('facturas/{factura}/cancelar', [FacturasController::class, 'cancelar'])->name('facturas.cancelar');
        Route::post('facturas/{factura}/refacturar', [FacturasController::class, 'refacturar'])->name('facturas.refacturar');
        Route::post('facturas/{factura}/firmar', [FacturasController::class, 'firmar'])->name('facturas.firmar');
        Route::post('facturas/{factura}/cobrar', [FacturasController::class, 'cobrar'])->name('facturas.cobrar');
        Route::get('aforos-pendientes', [FacturasController::class, 'aforosPendientes'])->name('aforos.pendientes');

        Route::resource('prefacturas', PrefacturasController::class)
            ->only(['index', 'create', 'store', 'update', 'destroy']);

        Route::resource('tipo-ingresos', TipoIngresosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo RRHH (Fase 5.5)
        Route::resource('bolsa', BolsaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('plantilla', PlantillaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('historial-movimientos', HistorialMovimientosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-incidencias', TiposIncidenciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-penalizaciones', TiposPenalizacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-contratos', TiposContratosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-sistemas-pago', TiposSistemasPagoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-pagos-adicionales', TiposPagosAdicionalesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-tasas', TiposTasasController::class)
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

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Comercial\AcuerdoController;
use App\Http\Controllers\Api\V1\Comercial\ClienteController;
use App\Http\Controllers\Api\V1\Comercial\LugareController;
use App\Http\Controllers\Api\V1\Combustible\CargaController as CombustibleCargaController;
use App\Http\Controllers\Api\V1\Combustible\DescargaController as CombustibleDescargaController;
use App\Http\Controllers\Api\V1\Combustible\ReporteCostoController;
use App\Http\Controllers\Api\V1\Combustible\TarjetaController;
use App\Http\Controllers\Api\V1\ContextoController;
use App\Http\Controllers\Api\V1\Flota\TractivoController;
use App\Http\Controllers\Api\V1\Ingresos\AforoController;
use App\Http\Controllers\Api\V1\Ingresos\FacturaController;
use App\Http\Controllers\Api\V1\Ingresos\IndicadorController;
use App\Http\Controllers\Api\V1\Rrhh\BolsaController;
use App\Http\Controllers\Api\V1\Rrhh\CargoController;
use App\Http\Controllers\Api\V1\Rrhh\SalarioController;
use App\Http\Controllers\Api\V1\Taller\ControlLubricanteController;
use App\Http\Controllers\Api\V1\Taller\OrdenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/

Route::get('ping', fn () => response()->json(['ok' => true, 'version' => 'v1']));

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'api.entidad', 'api.fecha'])->group(function () {
    // Autenticación
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

    // Contexto de trabajo (edita las abilities del token)
    Route::post('contexto/entidad', [ContextoController::class, 'entidad']);
    Route::post('contexto/fecha', [ContextoController::class, 'fecha']);

    // Flota · Tractivos
    Route::get('tractivos', [TractivoController::class, 'index']);
    Route::get('tractivos/{tractivo}', [TractivoController::class, 'show']);

    // RRHH
    Route::get('bolsa', [BolsaController::class, 'index']);
    Route::get('bolsa/{bolsa}', [BolsaController::class, 'show']);
    Route::get('cargos', [CargoController::class, 'index']);
    Route::get('cargos/{cargo}', [CargoController::class, 'show']);
    Route::get('salarios', [SalarioController::class, 'index']);
    Route::get('salarios/{salario}', [SalarioController::class, 'show']);

    // Comercial
    Route::get('clientes', [ClienteController::class, 'index']);
    Route::get('clientes/{cliente}', [ClienteController::class, 'show']);
    Route::get('lugares', [LugareController::class, 'index']);
    Route::get('lugares/{lugar}', [LugareController::class, 'show']);
    Route::get('acuerdos', [AcuerdoController::class, 'index']);
    Route::get('acuerdos/{acuerdo}', [AcuerdoController::class, 'show']);

    // Ingresos / Indicadores
    Route::get('aforos', [AforoController::class, 'index']);
    Route::get('aforos/{aforo}', [AforoController::class, 'show']);
    Route::get('facturas', [FacturaController::class, 'index']);
    Route::get('facturas/{factura}', [FacturaController::class, 'show']);
    Route::get('indicadores', [IndicadorController::class, 'index']);

    // Combustible
    Route::get('combustible/cargas', [CombustibleCargaController::class, 'index']);
    Route::get('combustible/cargas/{carga}', [CombustibleCargaController::class, 'show']);
    Route::get('combustible/descargas', [CombustibleDescargaController::class, 'index']);
    Route::get('combustible/descargas/{descarga}', [CombustibleDescargaController::class, 'show']);
    Route::get('tarjetas', [TarjetaController::class, 'index']);
    Route::get('tarjetas/{tarjeta}', [TarjetaController::class, 'show']);
    Route::get('reportes-costos', [ReporteCostoController::class, 'index']);
    Route::get('reportes-costos/{reporte}', [ReporteCostoController::class, 'show']);

    // Taller
    Route::get('taller/ordenes', [OrdenController::class, 'index']);
    Route::get('taller/ordenes/{orden}', [OrdenController::class, 'show']);
    Route::get('taller/control-lubricantes', [ControlLubricanteController::class, 'index']);
    Route::get('taller/control-lubricantes/{registro}', [ControlLubricanteController::class, 'show']);
});

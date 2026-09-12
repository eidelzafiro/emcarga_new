<?php

use App\Http\Controllers\AmortizacionTallerController;
use App\Http\Controllers\AcuerdosController;
use App\Http\Controllers\ExportacionController;
use App\Http\Controllers\AforosController;
use App\Http\Controllers\AlertasController;
use App\Http\Controllers\AreasController;
use App\Http\Controllers\AreasOrganigramaController;
use App\Http\Controllers\ArrastresController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BateriasController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\CajasController;
use App\Http\Controllers\CargosController;
use App\Http\Controllers\CartaPorteController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CierreTarjetasController;
use App\Http\Controllers\FusionCatalogosController;
use App\Http\Controllers\GastoMaterialController;
use App\Http\Controllers\ProductosController;
use App\Http\Controllers\ChoferesController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\CombustibleCargasController;
use App\Http\Controllers\CombustibleDescargasController;
use App\Http\Controllers\TarjetasController;
use App\Http\Controllers\ConciliacionesController;
use App\Http\Controllers\DietasController;
use App\Http\Controllers\ReembolsosController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\ConfiguracionesModeloController;
use App\Http\Controllers\ConsecutivosController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\ComercialController;
use App\Http\Controllers\ContenedoresController;
use App\Http\Controllers\CuadreContabilidadController;
use App\Http\Controllers\ContextoTrabajoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperativosController;
use App\Http\Controllers\DashboardTecnicoController;
use App\Http\Controllers\DemandasController;
use App\Http\Controllers\DetallesCargaCombustibleController;
use App\Http\Controllers\DevolucionesController;
use App\Http\Controllers\DiferencialesController;
use App\Http\Controllers\DistanciasController;
use App\Http\Controllers\EntidadesController;
use App\Http\Controllers\EstadisticasExplotacionController;
use App\Http\Controllers\EstadosTarjetasController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\FirmasController;
use App\Http\Controllers\FondosTiempoController;
use App\Http\Controllers\GruposEscalaController;
use App\Http\Controllers\HistorialMovimientosController;
use App\Http\Controllers\HistorialTractivosController;
use App\Http\Controllers\HojasRutaController;
use App\Http\Controllers\IncidenciasController;
use App\Http\Controllers\IndicadoresController;
use App\Http\Controllers\LubricantesController;
use App\Http\Controllers\TiposLubricantesController;
use App\Http\Controllers\ControlLubricanteController;
use App\Http\Controllers\LugaresController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MesesController;
use App\Http\Controllers\MotoresController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\NavesController;
use App\Http\Controllers\NeumaticosController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OsdesController;
use App\Http\Controllers\OtrosAgregadosController;
use App\Http\Controllers\OtrosGastosController;
use App\Http\Controllers\OtrosIngresosPreController;
use App\Http\Controllers\PagosAdicionalesCargoController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\PenalizacionesController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PizarraController;
use App\Http\Controllers\PrefacturasController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProvinciasController;
use App\Http\Controllers\RegistroOrdenesTallerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ReportesCostosController;
use App\Http\Controllers\SalariosAdministrativosController;
use App\Http\Controllers\ServicentrosController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\TallerController;
use App\Http\Controllers\TiposMantenimientoController;
use App\Http\Controllers\TalleresController;
use App\Http\Controllers\TarifasConfigController;
use App\Http\Controllers\TarifasController;
use App\Http\Controllers\TiposArrastresController;
use App\Http\Controllers\TiposCargasReporteController;
use App\Http\Controllers\TipoEquiposController;
use App\Http\Controllers\TiposTractivosController;
use App\Http\Controllers\TipoVehiculoController;
use App\Http\Controllers\TractivosController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacacionesController;
use App\Http\Controllers\VallasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect(request()->user() ? route('dashboard') : route('login')));

// Invitados
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    // Recuperación de contraseña (solo usuarios con correo guardado)
    Route::get('olvide-password', [ForgotPasswordController::class, 'solicitarForm'])->name('password.request');
    Route::post('olvide-password', [ForgotPasswordController::class, 'enviarEnlace'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('restablecer-password/{token}', [ForgotPasswordController::class, 'formularioReset'])->name('password.reset');
    Route::post('restablecer-password', [ForgotPasswordController::class, 'guardarReset'])
        ->middleware('throttle:5,1')->name('password.update');

    // Challenge 2FA (login diferido, aún sin autenticar)
    Route::get('two-factor', [TwoFactorController::class, 'create'])->name('two-factor.create');
    Route::post('two-factor', [TwoFactorController::class, 'store'])->name('two-factor.store');
});

// Autenticados
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // 2FA (auto-servicio, solo perfiles privilegiados)
    Route::get('two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    Route::get('perfil/cambiar-password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('perfil/cambiar-password', [PasswordController::class, 'update'])->name('password.update');

    // Perfil propio: datos personales + avatar
    Route::get('perfil', [\App\Http\Controllers\Auth\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('perfil', [\App\Http\Controllers\Auth\ProfileController::class, 'update'])->name('profile.update');
    Route::post('perfil/avatar', [\App\Http\Controllers\Auth\ProfileController::class, 'actualizarAvatar'])->name('profile.avatar');
    Route::delete('perfil/avatar', [\App\Http\Controllers\Auth\ProfileController::class, 'eliminarAvatar'])->name('profile.avatar.delete');

    // Contexto de trabajo: entidad activa y fecha de operaciones
    Route::post('contexto/entidad', [ContextoTrabajoController::class, 'cambiarEntidad'])->name('contexto.entidad');
    Route::post('contexto/perfil', [ContextoTrabajoController::class, 'cambiarPerfil'])->name('contexto.perfil');
    Route::post('contexto/fecha-operaciones', [ContextoTrabajoController::class, 'cambiarFechaOperaciones'])->name('contexto.fecha-operaciones');

    // API de KPIs (también accesible con password temporal para el dashboard)
    Route::get('api/kpis', [DashboardController::class, 'kpis'])
        ->middleware('permission:dashboard.ver')
        ->name('api.kpis');

    // Dashboards del módulo técnico (tres propuestas). Comparten el permiso
    // dashboard.ver para no introducir nuevos permisos; se filtran por la
    // entidad activa y el mes de operaciones de la sesión.
    Route::middleware(['password.temporal', 'permission:dashboard.ver'])->group(function () {
        Route::get('tecnico/flota', [DashboardTecnicoController::class, 'flota'])->name('tecnico.flota');
        Route::get('tecnico/taller', [DashboardTecnicoController::class, 'taller'])->name('tecnico.taller');
        Route::get('tecnico/componentes', [DashboardTecnicoController::class, 'componentes'])->name('tecnico.componentes');
        Route::get('api/tecnico/flota', [DashboardTecnicoController::class, 'datosFlota'])->name('api.tecnico.flota');
        Route::get('api/tecnico/taller', [DashboardTecnicoController::class, 'datosTaller'])->name('api.tecnico.taller');
        Route::get('api/tecnico/componentes', [DashboardTecnicoController::class, 'datosComponentes'])->name('api.tecnico.componentes');
        Route::get('api/tecnico/flota/detalle', [DashboardTecnicoController::class, 'detalleFlota'])->name('api.tecnico.flota.detalle');
        Route::get('tecnico/pizarra-operativa', [DashboardTecnicoController::class, 'pizarraOperativa'])->name('tecnico.dashboard');
        Route::get('api/tecnico/pizarra-operativa', [DashboardTecnicoController::class, 'datosPizarraOperativa'])->name('api.tecnico.dashboard');
    });

    // Las notificaciones se sirven incluso con password temporal
    Route::get('notificaciones', [NotificationsController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{id}/leer', [NotificationsController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('notificaciones/leer-todas', [NotificationsController::class, 'markAllAsRead'])->name('notificaciones.leer-todas');

    // Requieren contraseña definitiva (no temporal) y el permiso del módulo
    // (EnsureModulePermission infiere modulo.accion desde el nombre de la ruta)
    Route::middleware(['password.temporal', 'permiso.modulo'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Vistas de trabajo con pestañas (CRUD embebidos) por módulo.
        Route::get('operativos/operaciones', [OperativosController::class, 'operaciones'])->name('operativos.operaciones');
        Route::get('comercial/operaciones', [ComercialController::class, 'operaciones'])->name('comercial.operaciones');

        // Pizarra de vehículos en vivo (Fase 4.10)
        Route::get('pizarra', [PizarraController::class, 'index'])->name('pizarra.index');
        Route::get('api/pizarra', [PizarraController::class, 'datos'])->name('api.pizarra');

        // Módulo Técnico - Flota
        Route::resource('tractivos', TractivosController::class)
            ->only(['index', 'store', 'update', 'destroy', 'edit']);
        Route::post('tractivos/{tractivo}/estado', [TractivosController::class, 'cambiarEstado'])
            ->name('tractivos.estado');

        Route::resource('motores', MotoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('motores/{motore}/baja', [MotoresController::class, 'baja'])->name('motores.baja');

        Route::resource('cajas', CajasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('cajas/{caja}/baja', [CajasController::class, 'baja'])->name('cajas.baja');

        Route::resource('diferenciales', DiferencialesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('diferenciales/{diferencial}/baja', [DiferencialesController::class, 'baja'])->name('diferenciales.baja');

        Route::resource('baterias', BateriasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('baterias/{bateria}/movimiento', [BateriasController::class, 'registrarMovimiento'])
            ->name('baterias.movimiento');
        Route::post('baterias/{bateria}/baja', [BateriasController::class, 'darDeBaja'])
            ->name('baterias.baja');

        Route::resource('neumaticos', NeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('neumaticos/{neumatico}/movimiento', [NeumaticosController::class, 'registrarMovimiento'])
            ->name('neumaticos.movimiento');
        Route::post('neumaticos/{neumatico}/retirar', [NeumaticosController::class, 'retirar'])
            ->name('neumaticos.retirar');
        Route::get('neumaticos/{neumatico}/movimientos', [NeumaticosController::class, 'movimientos'])
            ->name('neumaticos.movimientos');

        Route::resource('lubricantes', LubricantesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-lubricantes', TiposLubricantesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('control-lubricante', ControlLubricanteController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('otros-agregados', OtrosAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Taller
        Route::resource('taller', TallerController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['taller' => 'ordene']);
        Route::resource('tipos-mantenimiento', TiposMantenimientoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('taller/plan-mtto', [TallerController::class, 'planMtto'])
            ->name('taller.plan-mtto');
        Route::post('taller/{ordene}/cerrar', [TallerController::class, 'cerrar'])
            ->name('taller.cerrar');
        Route::post('taller/{ordene}/cancelar', [TallerController::class, 'cancelar'])
            ->name('taller.cancelar');
        Route::post('taller/{ordene}/operaciones', [TallerController::class, 'agregarOperacion'])
            ->name('taller.operaciones');
        Route::put('taller/{ordene}/operaciones/{operacione}', [TallerController::class, 'actualizarOperacion'])
            ->name('taller.operaciones.update');
        Route::delete('taller/{ordene}/operaciones/{operacione}', [TallerController::class, 'eliminarOperacion'])
            ->name('taller.operaciones.destroy');
        Route::post('taller/{ordene}/gastos', [TallerController::class, 'agregarGasto'])
            ->name('taller.gastos');
        Route::put('taller/{ordene}/gastos/{gasto}', [TallerController::class, 'actualizarGasto'])
            ->name('taller.gastos.update');
        Route::delete('taller/{ordene}/gastos/{gasto}', [TallerController::class, 'eliminarGasto'])
            ->name('taller.gastos.destroy');
        Route::post('taller/{ordene}/movimientos', [TallerController::class, 'agregarMovimiento'])
            ->name('taller.movimientos');
        Route::put('taller/{ordene}/movimientos/{movimiento}', [TallerController::class, 'actualizarMovimiento'])
            ->name('taller.movimientos.update');
        Route::delete('taller/{ordene}/movimientos/{movimiento}', [TallerController::class, 'eliminarMovimiento'])
            ->name('taller.movimientos.destroy');

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

        Route::post('solicitudes/{solicitude}/duplicar', [SolicitudesController::class, 'duplicar'])
            ->name('solicitudes.duplicar');
        Route::post('solicitudes/{solicitude}/carta-porte', [SolicitudesController::class, 'registrarCartaPorte'])
            ->name('solicitudes.carta-porte');
        Route::post('solicitudes/{solicitude}/cancelar', [SolicitudesController::class, 'cancelar'])
            ->name('solicitudes.cancelar');

        Route::resource('carta-porte', CartaPorteController::class, ['parameters' => ['carta-porte' => 'carta']])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('carta-porte/validar-folio', [CartaPorteController::class, 'validarFolio'])
            ->name('carta-porte.validar-folio');
        Route::post('carta-porte/obtener-distancia', [CartaPorteController::class, 'obtenerDistancia'])
            ->name('carta-porte.obtener-distancia');
        Route::post('carta-porte/{carta}/recepcionar', [CartaPorteController::class, 'recepcionar'])
            ->name('carta-porte.recepcionar');

        Route::resource('hojas-ruta', HojasRutaController::class, ['parameters' => ['hojas-ruta' => 'hoja']])
            ->only(['index', 'store', 'update', 'destroy']);

        // Vistas de prueba del formato de tarjetas (solo lectura)
        Route::get('preview/hojas-ruta', [PreviewController::class, 'hojasRuta'])
            ->name('preview.hojas-ruta');
        Route::get('preview/solicitudes', [PreviewController::class, 'solicitudes'])
            ->name('preview.solicitudes');


        Route::resource('configuraciones-modelo', ConfiguracionesModeloController::class)
            ->only(['index', 'store', 'update', 'destroy']);


        Route::resource('tipos-cargas-reporte', TiposCargasReporteController::class)
            ->only(['index', 'store', 'update', 'destroy']);


        Route::resource('alertas', AlertasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('demandas', DemandasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tarifas', TarifasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('tarifas-config', [TarifasConfigController::class, 'edit'])
            ->name('tarifas-config.edit');
        Route::put('tarifas-config', [TarifasConfigController::class, 'update'])
            ->name('tarifas-config.update');

        Route::resource('otros-ingresos-pre', OtrosIngresosPreController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Facturación (Fase 5.4)
        Route::resource('facturas', FacturasController::class)
            ->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::post('facturas/{factura}/cancelar', [FacturasController::class, 'cancelar'])->name('facturas.cancelar');
        Route::post('facturas/{factura}/refacturar', [FacturasController::class, 'refacturar'])->name('facturas.refacturar');
        Route::post('facturas/{factura}/firmar', [FacturasController::class, 'firmar'])->name('facturas.firmar');
        Route::post('facturas/{factura}/cobrar', [FacturasController::class, 'cobrar'])->name('facturas.cobrar');
        Route::get('facturas/exportar', [FacturasController::class, 'exportar'])->name('facturas.exportar');
        Route::get('aforos-pendientes', [FacturasController::class, 'aforosPendientes'])->name('aforos.pendientes');

        Route::resource('aforos', AforosController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'show']);
        Route::post('aforos/cotizar', [AforosController::class, 'cotizar'])->name('aforos.cotizar');
        Route::post('aforos/cotizar-demora', [AforosController::class, 'cotizarDemora'])->name('aforos.cotizar-demora');
        Route::post('aforos/cotizar-almacenaje', [AforosController::class, 'cotizarAlmacenaje'])->name('aforos.cotizar-almacenaje');
        Route::post('aforos/cotizar-salario', [AforosController::class, 'cotizarSalario'])->name('aforos.cotizar-salario');
        Route::post('aforos/cotizar-tiempos', [AforosController::class, 'cotizarTiempos'])->name('aforos.cotizar-tiempos');
        Route::post('aforos/cotizar-dif-horas', [AforosController::class, 'cotizarDifHoras'])->name('aforos.cotizar-dif-horas');
        Route::post('aforos/cotizar-indicadores', [AforosController::class, 'cotizarIndicadores'])->name('aforos.cotizar-indicadores');

        // Editor de indicadores de explotación por aforo
        Route::get('indicadores', [IndicadoresController::class, 'index'])->name('indicadores.index');
        Route::put('indicadores/{aforo}', [IndicadoresController::class, 'update'])->name('indicadores.update');

        Route::resource('prefacturas', PrefacturasController::class)
            ->only(['index', 'create', 'store', 'update', 'destroy']);
        Route::post('prefacturas/{prefactura}/facturar', [PrefacturasController::class, 'facturar'])->name('prefacturas.facturar');


        // Módulo RRHH (Fase 5.5)
        Route::resource('bolsa', BolsaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Coeficiente CDS por entidad+mes+año (Sistema de Pago por Resultados)
        Route::resource('cds', \App\Http\Controllers\CdsController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('historial-movimientos', HistorialMovimientosController::class)
            ->only(['index']);
        Route::post('historial-movimientos/alta', [HistorialMovimientosController::class, 'alta'])->name('historial-movimientos.alta');
        Route::post('historial-movimientos/traslado', [HistorialMovimientosController::class, 'traslado'])->name('historial-movimientos.traslado');
        Route::post('historial-movimientos/baja', [HistorialMovimientosController::class, 'baja'])->name('historial-movimientos.baja');







        // RRHH - Tablas faltantes (Fase 5.5 parte 3)
        Route::resource('provincias', ProvinciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('municipios', MunicipiosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('osdes', OsdesController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['osdes' => 'id']);

        Route::resource('firmas', FirmasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('meses', MesesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('fondos-tiempo', FondosTiempoController::class)
            ->only(['index', 'store', 'update', 'destroy']);



        Route::resource('salarios-administrativos', SalariosAdministrativosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('salarios-administrativos/guardar-turno', [SalariosAdministrativosController::class, 'guardarTurno'])
            ->name('salarios-administrativos.guardar-turno');

        Route::resource('salarios-choferes', \App\Http\Controllers\SalariosChoferesController::class)
            ->only(['index']);

        Route::post('salarios-choferes/actualizar-tasa', [\App\Http\Controllers\SalariosChoferesController::class, 'actualizarTasa'])
            ->name('salarios-choferes.actualizar-tasa');

        Route::post('salarios-choferes/guardar-tiempo', [\App\Http\Controllers\SalariosChoferesController::class, 'guardarTiempo'])
            ->name('salarios-choferes.guardar-tiempo');

        Route::post('salarios-choferes/editar-detalle', [\App\Http\Controllers\SalariosChoferesController::class, 'editarDetalle'])
            ->name('salarios-choferes.editar-detalle');

        // RRHH - Catálogos pequeños (Fase 5.5 parte 3)




        // Catálogos y configuración (Fase 5.7)



        Route::resource('naves', NavesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('vallas', VallasController::class)
            ->only(['index', 'store', 'update', 'destroy']);




        Route::resource('consecutivos', ConsecutivosController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['consecutivos' => 'id']);





        Route::resource('talleres', TalleresController::class)
            ->only(['index', 'store', 'update', 'destroy']);









        Route::resource('grupos-escala', GruposEscalaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['grupos-escala' => 'id']);

        Route::resource('entidades', EntidadesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Reportes PDF/Excel (Fase 6)
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('marcas', [ReportController::class, 'pdfMarcas'])->name('marcas');
            Route::get('modelos', [ReportController::class, 'pdfModelos'])->name('modelos');
            Route::get('paises', [ReportController::class, 'pdfPaises'])->name('paises');
            Route::get('salario-prenomina', [ReportController::class, 'pdfSalarioPrenomina'])->name('salario-prenomina');
            Route::get('salario-choferes', [ReportController::class, 'pdfSalarioChoferes'])->name('salario-choferes');
            Route::get('prenomina-choferes', [ReportController::class, 'pdfPrenominaChoferes'])->name('prenomina-choferes');
            Route::get('prenomina-administrativo', [ReportController::class, 'pdfPrenominaAdministrativo'])->name('prenomina-administrativo');
            Route::get('cumpleanos', [ReportController::class, 'pdfCumpleanos'])->name('cumpleanos');
            Route::get('licencia-conduccion', [ReportController::class, 'pdfLicenciaConduccion'])->name('licencia-conduccion');
            Route::get('adicionales', [ReportController::class, 'pdfAdicionales'])->name('adicionales');
            Route::get('nocturnidad', [ReportController::class, 'pdfNocturnidad'])->name('nocturnidad');
            Route::get('pago-administrativo', [ReportController::class, 'pdfPagoAdministrativo'])->name('pago-administrativo');
            Route::get('resumen-tiempos-choferes', [ReportController::class, 'pdfResumenTiemposChoferes'])->name('resumen-tiempos-choferes');
            Route::get('analisis-salario-transportacion', [ReportController::class, 'pdfAnalisisSalarioTransportacion'])->name('analisis-salario-transportacion');
            Route::get('control-diario-administrativo', [ReportController::class, 'pdfControlDiarioAdministrativo'])->name('control-diario-administrativo');
            Route::get('control-diario-choferes', [ReportController::class, 'pdfControlDiarioChoferes'])->name('control-diario-choferes');
            Route::get('exportar-versat', [ReportController::class, 'exportarVersat'])->name('exportar-versat');
            Route::get('incidencias', [ReportController::class, 'pdfIncidencias'])->name('incidencias');
            Route::get('modelo1', [ReportController::class, 'pdfModelo1'])->name('modelo1');
            Route::get('prenomina-choferes-excel', [ReportController::class, 'excelPrenominaChoferes'])->name('prenomina-choferes-excel');
            Route::get('prenomina-administrativo-excel', [ReportController::class, 'excelPrenominaAdministrativo'])->name('prenomina-administrativo-excel');
            Route::get('modelo1-excel', [ReportController::class, 'excelModelo1'])->name('modelo1-excel');
            Route::get('ingresos-tractivo', [ReportController::class, 'pdfIngresosTractivos'])->name('ingresos-tractivo');
            Route::get('ingresos-choferes', [ReportController::class, 'pdfIngresosChoferes'])->name('ingresos-choferes');
            // Página de filtros del Modelo 1 (selector mes + chofer).
            Route::get('modelo1/filtros', [ReportesController::class, 'modelo1'])->name('modelo1.filtros');
            // Página de filtros del Resumen de Ingresos / Indicadores de Explotación.
            Route::get('resumen', [ReportesController::class, 'resumen'])->name('resumen');
            Route::get('resumen/{formato}', [ReportesController::class, 'resumenGenerar'])->name('resumen.generar');
            // Página de Salarios y Prenóminas (choferes/administrativo) + Modelo 1.
            Route::get('salarios', [ReportesController::class, 'salarios'])->name('salarios');
            // Fase A: catálogo de reportes usados (índice con filtros reutilizables)
            Route::get('catalogo', [ReportesController::class, 'index'])->name('catalogo');
            // Reportes del grupo DOCUMENTOS (cartas de porte y hojas de ruta)
            Route::get('documentos', [ReportesController::class, 'documentos'])->name('documentos');
            Route::get('documentos/generar/{reporte}', [ReportesController::class, 'documentosGenerar'])->name('documentos.generar');
            // GET: descarga/binario vía navegación real (sin XHR Inertia). POST: flujo
            // Inertia para exportaciones en cola (feedback en la misma página).
            Route::match(['get', 'post'], 'generar/{reporte}', [ReportesController::class, 'generar'])->name('generar');
        });

        // R-3: descarga de exportaciones de tablas generadas en cola
        Route::get('exportaciones/descargar/{token}', [ExportacionController::class, 'descargar'])
            ->name('exportaciones.descargar');

        // Impresión de documentos: permiso del módulo del recurso (carta-porte.ver,
        // hojas-ruta.ver, facturas.ver, prefacturas.ver) en vez de reportes.ver.
        Route::prefix('reportes')->group(function () {
            Route::get('factura/{factura}', [ReportController::class, 'pdfFactura'])->name('facturas.imprimir');
            Route::get('prefactura/{prefactura}', [ReportController::class, 'pdfPrefactura'])->name('prefacturas.imprimir');
            Route::get('carta-porte/{carta}', [ReportController::class, 'pdfCartaPorte'])->name('carta-porte.imprimir');
            Route::get('hoja-ruta/{hoja}', [ReportController::class, 'pdfHojaRuta'])->name('hojas-ruta.imprimir');
            Route::get('aforo/{aforo}', [ReportController::class, 'pdfAforo'])->name('aforos.imprimir');
            // Impresión sobre formato impreso (pre-impreso), coordenadas configurables.
            Route::get('carta-porte/{carta}/emision', [ReportController::class, 'pdfCpEmision'])->name('carta-porte.emision');
            Route::get('aforo/{aforo}/impreso', [ReportController::class, 'pdfCpAforo'])->name('aforo.impreso');
            Route::get('hoja-ruta/{hoja}/emision', [ReportController::class, 'pdfHrEmision'])->name('hoja-ruta.emision');
            // Módulo Técnico
            Route::get('plan-bajas-neumaticos', [ReportController::class, 'pdfPlanBajasNeumaticos'])->name('plan-bajas-neumaticos.imprimir');
            Route::get('control-lubricante', [ReportController::class, 'pdfControlLubricante'])->name('control-lubricante.imprimir');
            Route::get('orden-taller/{id}', [ReportController::class, 'pdfOrdenTaller'])->name('orden-taller.imprimir');
        });

        // Módulo Contabilidad (Fase 5.6)
        Route::resource('conciliaciones', ConciliacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);


        Route::resource('otros-gastos', OtrosGastosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('otros-gastos/tipo-concepto', [OtrosGastosController::class, 'storeTipoConcepto'])
            ->name('otros-gastos.store-tipo-concepto');

        Route::get('cuadre-contabilidad', [CuadreContabilidadController::class, 'index'])
            ->name('cuadre-contabilidad.index');

        Route::get('contabilidad/dashboard', [ContabilidadController::class, 'dashboard'])
            ->name('contabilidad.dashboard');

        Route::resource('gasto-material', GastoMaterialController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('amortizacion-taller', AmortizacionTallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('combustible-cargas', CombustibleCargasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tarjetas', TarjetasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('cierre-tarjetas', [CierreTarjetasController::class, 'index'])->name('cierre-tarjetas.index');
        Route::post('cierre-tarjetas', [CierreTarjetasController::class, 'store'])->name('cierre-tarjetas.store');

        Route::resource('combustible-descargas', CombustibleDescargasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Tablas faltantes Contabilidad (Fase 5.6 parte 3)
        Route::resource('servicentros', ServicentrosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('reportes-costos', ReportesCostosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::post('reportes-costos/recalcular', [ReportesCostosController::class, 'recalcular'])
            ->name('reportes-costos.recalcular');

        Route::post('reportes-costos/recalcular-todos', [ReportesCostosController::class, 'recalcularTodos'])
            ->name('reportes-costos.recalcular-todos');

        Route::resource('estados-tarjetas', EstadosTarjetasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('detalles-carga-combustible', DetallesCargaCombustibleController::class)
            ->only(['index', 'store', 'update', 'destroy']);


        Route::resource('dietas', DietasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('dietas/{dieta}/liquidar', [DietasController::class, 'liquidar'])
            ->name('dietas.liquidar');
        Route::post('dietas/{dieta}/cancelar', [DietasController::class, 'cancelar'])
            ->name('dietas.cancelar');

        Route::resource('reembolsos', ReembolsosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('reembolsos/{reembolso}/aprobar', [ReembolsosController::class, 'aprobar'])
            ->name('reembolsos.aprobar');
        Route::post('reembolsos/{reembolso}/rechazar', [ReembolsosController::class, 'rechazar'])
            ->name('reembolsos.rechazar');

        Route::resource('plantilla', PlantillaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('pagos', PagosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Técnica - Tablas faltantes (Fase 5.8)
        Route::resource('arrastres', ArrastresController::class)
            ->parameters(['arrastres' => 'arrastre'])
            ->only(['index', 'store', 'update', 'destroy', 'edit']);
        Route::post('arrastres/{arrastre}/estado', [ArrastresController::class, 'cambiarEstado'])
            ->name('arrastres.estado');
        Route::resource('tipos-tractivos', TiposTractivosController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['tipos-tractivos' => 'id']);
        Route::resource('tipos-arrastres', TiposArrastresController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['tipos-arrastres' => 'id']);
        Route::resource('tipo-vehiculos', TipoVehiculoController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['tipo-vehiculos' => 'tipoVehiculo']);
        Route::resource('tipos-equipos', TipoEquiposController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['tipos-equipos' => 'id']);
        Route::resource('historial-tractivos', HistorialTractivosController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['historial-tractivos' => 'id']);

        // RRHH - Tasas
        Route::resource('tasas', \App\Http\Controllers\TasasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // Comercial - Tablas faltantes (Fase 5.8)
        Route::resource('contenedores', ContenedoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('productos', ProductosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // Misc - Tablas varias (Fase 5.8)
        Route::resource('choferes', ChoferesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('devoluciones', DevolucionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('incidencias', IncidenciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('penalizaciones/obtener-empleado', [PenalizacionesController::class, 'obtenerEmpleado'])
            ->name('penalizaciones.obtener-empleado');
        Route::resource('penalizaciones', PenalizacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('estadisticas-explotacion', EstadisticasExplotacionController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('registro-ordenes-taller', RegistroOrdenesTallerController::class)
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

        // Administración de menú (Fase 5.9)
        Route::resource('menu-items', MenuItemController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['menu-items' => 'menuItem']);
        Route::post('menu-items/{menuItem}/toggle-visibility/{role}', [MenuItemController::class, 'toggleVisibility'])
            ->name('menu-items.toggle-visibility');
        Route::post('menu-items/reordenar', [MenuItemController::class, 'reordenar'])
            ->name('menu-items.reordenar');
        Route::post('menu-items/batch-toggle', [MenuItemController::class, 'batchToggleVisibility'])
            ->name('menu-items.batch-toggle');

        // Catálogo unificado (Fase 6.1)
        Route::get('catalogo', [CatalogoController::class, 'tipos'])->name('catalogo.tipos');
        Route::get('catalogo/gestionar', [CatalogoController::class, 'gestionar'])->name('catalogo.gestionar');
        Route::put('catalogo/{tipo}', [CatalogoController::class, 'updateTipo'])->name('catalogo.update-tipo');
        Route::get('catalogo/{tipo}', [CatalogoController::class, 'index'])->name('catalogo.index');
        Route::post('catalogo/{tipo}', [CatalogoController::class, 'store'])->name('catalogo.store');
        Route::put('catalogo/{tipo}/{id}', [CatalogoController::class, 'update'])->name('catalogo.update');
        Route::delete('catalogo/{tipo}/{id}', [CatalogoController::class, 'destroy'])->name('catalogo.destroy');

        // Fusión de catálogos (tipos de equipo / marcas / modelos)
        Route::get('fusionar-catalogos', [FusionCatalogosController::class, 'index'])->name('fusionar-catalogos.index');
        Route::get('fusionar-catalogos/opciones', [FusionCatalogosController::class, 'opciones'])->name('fusionar-catalogos.opciones');
        Route::post('fusionar-catalogos', [FusionCatalogosController::class, 'store'])->name('fusionar-catalogos.store');

        // Rutas directas para tipos del catálogo unificado (acceso desde menú)
        Route::resource('areas', AreasController::class);
        Route::get('cargos', [CargosController::class, 'index'])->name('cargos.index');
    });
});

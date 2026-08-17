<?php

use App\Http\Controllers\AcuerdosController;
use App\Http\Controllers\AforosController;
use App\Http\Controllers\AlertasController;
use App\Http\Controllers\AreasController;
use App\Http\Controllers\ArrastresController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BateriasController;
use App\Http\Controllers\BolsaController;
use App\Http\Controllers\CajasController;
use App\Http\Controllers\CargosController;
use App\Http\Controllers\CartaPorteController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriasCargoController;
use App\Http\Controllers\CategoriasProductosController;
use App\Http\Controllers\CentrosCostosController;
use App\Http\Controllers\ChoferesController;
use App\Http\Controllers\ClasificacionesOrdenesTallerController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ColoresController;
use App\Http\Controllers\CombustibleCargasController;
use App\Http\Controllers\CombustibleDescargasController;
use App\Http\Controllers\TarjetasController;
use App\Http\Controllers\CombustiblesLubricantesController;use App\Http\Controllers\ConciliacionesController;
use App\Http\Controllers\ConfiguracionesModeloController;
use App\Http\Controllers\ConsecutivosController;
use App\Http\Controllers\ContenedoresController;
use App\Http\Controllers\ContextoTrabajoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandasController;
use App\Http\Controllers\DescuentosEmpleadosController;
use App\Http\Controllers\DestinosAgregadosController;
use App\Http\Controllers\DetallesCargaCombustibleController;
use App\Http\Controllers\DevolucionesController;
use App\Http\Controllers\DiferencialesController;
use App\Http\Controllers\DistanciasController;
use App\Http\Controllers\ElementosGastoController;
use App\Http\Controllers\EmbalajesController;
use App\Http\Controllers\EmpleadosController;
use App\Http\Controllers\EntidadesController;
use App\Http\Controllers\EstadisticasExplotacionController;
use App\Http\Controllers\EstadosTarjetasController;
use App\Http\Controllers\FacturasController;
use App\Http\Controllers\FirmasAutorizadasController;
use App\Http\Controllers\FirmasController;
use App\Http\Controllers\FondosTiempoController;
use App\Http\Controllers\GruposController;
use App\Http\Controllers\GruposEscalaController;
use App\Http\Controllers\HistorialMovimientosController;
use App\Http\Controllers\HistorialTractivosController;
use App\Http\Controllers\HojasRutaController;
use App\Http\Controllers\IncidenciasController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LubricantesController;
use App\Http\Controllers\ControlLubricanteController;
use App\Http\Controllers\LugaresController;
use App\Http\Controllers\MarcasController;
use App\Http\Controllers\MedidasNeumaticosController;
use App\Http\Controllers\MediosProteccionController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MesesController;
use App\Http\Controllers\ModelosController;
use App\Http\Controllers\MotivosBajaBateriaController;
use App\Http\Controllers\MotivosEntradaTallerController;
use App\Http\Controllers\MotoresController;
use App\Http\Controllers\MovimientosInventarioController;
use App\Http\Controllers\MunicipiosController;
use App\Http\Controllers\NavesController;
use App\Http\Controllers\NavierasController;
use App\Http\Controllers\NeumaticosController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OrganismosController;
use App\Http\Controllers\OsdesController;
use App\Http\Controllers\OtrosAgregadosController;
use App\Http\Controllers\OtrosGastosController;
use App\Http\Controllers\OtrosIngresosPreController;
use App\Http\Controllers\PagosAdicionalesCargoController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\PaisesController;
use App\Http\Controllers\PenalizacionesController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PizarraTractivosController;
use App\Http\Controllers\PosicionesNeumaticosController;
use App\Http\Controllers\PrefacturasController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProvinciasController;
use App\Http\Controllers\RegistroOrdenesTallerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportesCostosController;
use App\Http\Controllers\SalariosAdministrativosController;
use App\Http\Controllers\SalariosController;
use App\Http\Controllers\ServicentrosController;
use App\Http\Controllers\SolicitudesController;
use App\Http\Controllers\TallerController;
use App\Http\Controllers\TalleresController;
use App\Http\Controllers\TarifasConfigController;
use App\Http\Controllers\TarifasController;
use App\Http\Controllers\TipoAgregadosController;
use App\Http\Controllers\TipoEquiposController;
use App\Http\Controllers\TipoIngresosController;
use App\Http\Controllers\TipoNeumaticosController;
use App\Http\Controllers\TiposAceitesController;
use App\Http\Controllers\TiposArrrastresController;
use App\Http\Controllers\TiposCargasReporteController;
use App\Http\Controllers\TiposCatalogoLugaresController;
use App\Http\Controllers\TiposColorPielController;
use App\Http\Controllers\TiposCombustiblesController;
use App\Http\Controllers\TiposConceptosController;
use App\Http\Controllers\TiposContratosController;
use App\Http\Controllers\TiposDeduccionesController;
use App\Http\Controllers\TiposDocumentosController;
use App\Http\Controllers\TiposEstadoCivilController;
use App\Http\Controllers\TiposEstadosController;
use App\Http\Controllers\TiposGastosController;
use App\Http\Controllers\TiposGrupoHorarioController;
use App\Http\Controllers\TiposIncidenciasController;
use App\Http\Controllers\TiposIntegracionPoliticaController;
use App\Http\Controllers\TiposMantenimientosController;
use App\Http\Controllers\TiposMediosCargoController;
use App\Http\Controllers\TiposMediosProteccionController;
use App\Http\Controllers\TiposModeloController;
use App\Http\Controllers\TiposNivelEducacionController;
use App\Http\Controllers\TiposPagosAdicionalesController;
use App\Http\Controllers\TiposPenalizacionesController;
use App\Http\Controllers\TiposRoturasController;
use App\Http\Controllers\TiposServiciosController;
use App\Http\Controllers\TiposSexoController;
use App\Http\Controllers\TiposSistemasController;
use App\Http\Controllers\TiposSistemasPagoController;
use App\Http\Controllers\TiposSuspensionController;
use App\Http\Controllers\TiposTasasController;
use App\Http\Controllers\TiposTractivosController;
use App\Http\Controllers\TiposUbicacionDefensaController;
use App\Http\Controllers\TractivosController;
use App\Http\Controllers\TurnosComercialesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacacionesController;
use App\Http\Controllers\ValesController;
use App\Http\Controllers\VallasController;
use Illuminate\Support\Facades\Route;

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

    // Contexto de trabajo: entidad activa y fecha de operaciones
    Route::post('contexto/entidad', [ContextoTrabajoController::class, 'cambiarEntidad'])->name('contexto.entidad');
    Route::post('contexto/perfil', [ContextoTrabajoController::class, 'cambiarPerfil'])->name('contexto.perfil');
    Route::post('contexto/fecha-operaciones', [ContextoTrabajoController::class, 'cambiarFechaOperaciones'])->name('contexto.fecha-operaciones');

    // API de KPIs (también accesible con password temporal para el dashboard)
    Route::get('api/kpis', [DashboardController::class, 'kpis'])
        ->middleware('permission:dashboard.ver')
        ->name('api.kpis');

    // Las notificaciones se sirven incluso con password temporal
    Route::get('notificaciones', [NotificationsController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{id}/leer', [NotificationsController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('notificaciones/leer-todas', [NotificationsController::class, 'markAllAsRead'])->name('notificaciones.leer-todas');

    // Requieren contraseña definitiva (no temporal) y el permiso del módulo
    // (EnsureModulePermission infiere modulo.accion desde el nombre de la ruta)
    Route::middleware(['password.temporal', 'permiso.modulo'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Pizarra de vehículos en vivo (Fase 4.10)
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

        Route::resource('control-lubricante', ControlLubricanteController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('otros-agregados', OtrosAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo Taller
        Route::resource('taller', TallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('taller/{ordene}/cerrar', [TallerController::class, 'cerrar'])
            ->name('taller.cerrar');
        Route::post('taller/{ordene}/cancelar', [TallerController::class, 'cancelar'])
            ->name('taller.cancelar');
        Route::post('taller/{ordene}/operaciones', [TallerController::class, 'agregarOperacion'])
            ->name('taller.operaciones');
        Route::post('taller/{ordene}/gastos', [TallerController::class, 'agregarGasto'])
            ->name('taller.gastos');
        Route::post('taller/{ordene}/movimientos', [TallerController::class, 'agregarMovimiento'])
            ->name('taller.movimientos');

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

        // Comercial - Tablas faltantes (Fase 5.3 parte 2)
        Route::resource('tipos-catalogo-lugares', TiposCatalogoLugaresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-modelo', TiposModeloController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('configuraciones-modelo', ConfiguracionesModeloController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-estados', TiposEstadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-cargas-reporte', TiposCargasReporteController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('turnos-comerciales', TurnosComercialesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('alertas', AlertasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('demandas', DemandasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('pizarra-tractivos', PizarraTractivosController::class)
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

        Route::resource('prefacturas', PrefacturasController::class)
            ->only(['index', 'create', 'store', 'update', 'destroy']);
        Route::post('prefacturas/{prefactura}/facturar', [PrefacturasController::class, 'facturar'])->name('prefacturas.facturar');

        Route::resource('tipo-ingresos', TipoIngresosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Módulo RRHH (Fase 5.5)
        Route::resource('bolsa', BolsaController::class)
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

        // RRHH - Tablas faltantes (Fase 5.5 parte 3)
        Route::resource('provincias', ProvinciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('municipios', MunicipiosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('osdes', OsdesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('firmas', FirmasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('meses', MesesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('fondos-tiempo', FondosTiempoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('medios-proteccion', MediosProteccionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-medios-cargo', TiposMediosCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('salarios', SalariosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('salarios-administrativos', SalariosAdministrativosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // RRHH - Catálogos pequeños (Fase 5.5 parte 3)
        Route::resource('tipos-color-piel', TiposColorPielController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-deducciones', TiposDeduccionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-estado-civil', TiposEstadoCivilController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-grupo-horario', TiposGrupoHorarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-integracion-politica', TiposIntegracionPoliticaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-medios-proteccion', TiposMediosProteccionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-nivel-educacion', TiposNivelEducacionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-sexo', TiposSexoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-ubicacion-defensa', TiposUbicacionDefensaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Catálogos y configuración (Fase 5.7)
        Route::resource('marcas', MarcasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('modelos', ModelosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('paises', PaisesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('naves', NavesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('vallas', VallasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('destinos-agregados', DestinosAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('medidas-neumaticos', MedidasNeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-combustibles', TiposCombustiblesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('consecutivos', ConsecutivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-servicios', TiposServiciosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-gastos', TiposGastosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('grupos', GruposController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('colores', ColoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('talleres', TalleresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-equipos', TipoEquiposController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-mantenimientos', TiposMantenimientosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-agregados', TipoAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-neumaticos', TipoNeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('posiciones-neumaticos', PosicionesNeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('embalajes', EmbalajesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('navieras', NavierasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('organismos', OrganismosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('categorias-cargo', CategoriasCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('grupos-escala', GruposEscalaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('entidades', EntidadesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Reportes PDF/Excel (Fase 6)
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('marcas', [ReportController::class, 'pdfMarcas'])->name('marcas');
            Route::get('modelos', [ReportController::class, 'pdfModelos'])->name('modelos');
            Route::get('paises', [ReportController::class, 'pdfPaises'])->name('paises');
            Route::get('salario-prenomina', [ReportController::class, 'pdfSalarioPrenomina'])->name('salario-prenomina');
            Route::get('salario-choferes', [ReportController::class, 'pdfSalarioChoferes'])->name('salario-choferes');
        });

        // Impresión de documentos: permiso del módulo del recurso (carta-porte.ver,
        // hojas-ruta.ver, facturas.ver, prefacturas.ver) en vez de reportes.ver.
        Route::prefix('reportes')->group(function () {
            Route::get('factura/{factura}', [ReportController::class, 'pdfFactura'])->name('facturas.imprimir');
            Route::get('prefactura/{prefactura}', [ReportController::class, 'pdfPrefactura'])->name('prefacturas.imprimir');
            Route::get('carta-porte/{carta}', [ReportController::class, 'pdfCartaPorte'])->name('carta-porte.imprimir');
            Route::get('hoja-ruta/{hoja}', [ReportController::class, 'pdfHojaRuta'])->name('hojas-ruta.imprimir');
            Route::get('aforo/{aforo}', [ReportController::class, 'pdfAforo'])->name('aforos.imprimir');
            // Módulo Técnico
            Route::get('plan-bajas-neumaticos', [ReportController::class, 'pdfPlanBajasNeumaticos'])->name('plan-bajas-neumaticos.imprimir');
            Route::get('control-lubricante', [ReportController::class, 'pdfControlLubricante'])->name('control-lubricante.imprimir');
            Route::get('orden-taller/{id}', [ReportController::class, 'pdfOrdenTaller'])->name('orden-taller.imprimir');
        });

        // Módulo Contabilidad (Fase 5.6)
        Route::resource('conciliaciones', ConciliacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-conceptos', TiposConceptosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('otros-gastos', OtrosGastosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('combustible-cargas', CombustibleCargasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tarjetas', TarjetasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('combustible-descargas', CombustibleDescargasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('inventario', InventarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('vales', ValesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Tablas faltantes Contabilidad (Fase 5.6 parte 3)
        Route::resource('servicentros', ServicentrosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-documentos', TiposDocumentosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('firmas-autorizadas', FirmasAutorizadasController::class)
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

        Route::resource('combustibles-lubricantes', CombustiblesLubricantesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('pagos', PagosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Técnica - Tablas faltantes (Fase 5.8)
        Route::resource('arrastres', ArrastresController::class)
            ->parameters(['arrastres' => 'tractivo'])
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-tractivos', TiposTractivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-arrastres', TiposArrrastresController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('historial-tractivos', HistorialTractivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('motivos-baja-bateria', MotivosBajaBateriaController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('motivos-entrada-taller', MotivosEntradaTallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-roturas', TiposRoturasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('clasificaciones-ordenes-taller', ClasificacionesOrdenesTallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-sistemas', TiposSistemasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-suspension', TiposSuspensionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // ATM - Inventario (Fase 5.8)
        Route::resource('movimientos-inventario', MovimientosInventarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // RRHH - Tablas faltantes (Fase 5.8)
        Route::resource('centros-costos', CentrosCostosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('pagos-adicionales-cargo', PagosAdicionalesCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // Comercial - Tablas faltantes (Fase 5.8)
        Route::resource('contenedores', ContenedoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categorias-productos', CategoriasProductosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // Misc - Tablas varias (Fase 5.8)
        Route::resource('tipos-aceites', TiposAceitesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('elementos-gasto', ElementosGastoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('choferes', ChoferesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('empleados', EmpleadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('devoluciones', DevolucionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('descuentos-empleados', DescuentosEmpleadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('incidencias', IncidenciasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('penalizaciones', PenalizacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('vacaciones', VacacionesController::class)
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

        // Catálogo unificado (Fase 6.1)
        Route::get('catalogo', [CatalogoController::class, 'tipos'])->name('catalogo.tipos');
        Route::get('catalogo/gestionar', [CatalogoController::class, 'gestionar'])->name('catalogo.gestionar');
        Route::put('catalogo/{tipo}', [CatalogoController::class, 'updateTipo'])->name('catalogo.update-tipo');
        Route::get('catalogo/{tipo}', [CatalogoController::class, 'index'])->name('catalogo.index');
        Route::post('catalogo/{tipo}', [CatalogoController::class, 'store'])->name('catalogo.store');
        Route::put('catalogo/{tipo}/{id}', [CatalogoController::class, 'update'])->name('catalogo.update');
        Route::delete('catalogo/{tipo}/{id}', [CatalogoController::class, 'destroy'])->name('catalogo.destroy');

        // Rutas directas para tipos del catálogo unificado (acceso desde menú)
        Route::get('areas', [AreasController::class, 'index'])->name('areas.index');
        Route::get('cargos', [CargosController::class, 'index'])->name('cargos.index');
    });
});

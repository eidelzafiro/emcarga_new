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
use App\Http\Controllers\CombustibleCargasController;
use App\Http\Controllers\CombustibleDescargasController;
use App\Http\Controllers\ConciliacionesController;
use App\Http\Controllers\HistorialMovimientosController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\OtrosGastosController;
use App\Http\Controllers\TiposConceptosController;
use App\Http\Controllers\ValesController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\TiposContratosController;
use App\Http\Controllers\TiposIncidenciasController;
use App\Http\Controllers\TiposPagosAdicionalesController;
use App\Http\Controllers\TiposPenalizacionesController;
use App\Http\Controllers\TiposSistemasPagoController;
use App\Http\Controllers\TiposTasasController;
use App\Http\Controllers\BuquesController;
use App\Http\Controllers\CategoriasCargoController;
use App\Http\Controllers\ColoresController;
use App\Http\Controllers\ConsecutivosController;
use App\Http\Controllers\DestinosAgregadosController;
use App\Http\Controllers\EmbalajesController;
use App\Http\Controllers\GruposController;
use App\Http\Controllers\GruposEscalaController;
use App\Http\Controllers\MarcasController;
use App\Http\Controllers\MedidasNeumaticosController;
use App\Http\Controllers\ModelosController;
use App\Http\Controllers\NavesController;
use App\Http\Controllers\NavierasController;
use App\Http\Controllers\OrganismosController;
use App\Http\Controllers\PaisesController;
use App\Http\Controllers\PosicionesNeumaticosController;
use App\Http\Controllers\TalleresController;
use App\Http\Controllers\TipoAgregadosController;
use App\Http\Controllers\TipoEquiposController;
use App\Http\Controllers\TipoNeumaticosController;
use App\Http\Controllers\TiposCombustiblesController;
use App\Http\Controllers\TiposServiciosController;
use App\Http\Controllers\TiposGastosController;
use App\Http\Controllers\VallasController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccionesHotkeysController;
use App\Http\Controllers\ArrastresController;
use App\Http\Controllers\BalancesElectricosController;
use App\Http\Controllers\CategoriasProductosController;
use App\Http\Controllers\CausasGpsController;
use App\Http\Controllers\CausasMultasController;
use App\Http\Controllers\CentrosCostosController;
use App\Http\Controllers\ChoferesController;
use App\Http\Controllers\ClasificacionesOrdenesTallerController;
use App\Http\Controllers\ClientesMmController;
use App\Http\Controllers\CompetenciasCargoController;
use App\Http\Controllers\ContenedoresController;
use App\Http\Controllers\DescuentosEmpleadosController;
use App\Http\Controllers\DetalleMovimientosInventarioController;
use App\Http\Controllers\DetallePrefacturasController;
use App\Http\Controllers\DetalleValesInventarioController;
use App\Http\Controllers\DevolucionesController;
use App\Http\Controllers\ElementosGastoController;
use App\Http\Controllers\EmpleadosController;
use App\Http\Controllers\EstadisticasExplotacionController;
use App\Http\Controllers\FuncionesCargoController;
use App\Http\Controllers\HistorialTractivosController;
use App\Http\Controllers\HotkeysController;
use App\Http\Controllers\ImportesGpsController;
use App\Http\Controllers\ImportesMultasController;
use App\Http\Controllers\LineasBateriaController;
use App\Http\Controllers\LineasDiferencialController;
use App\Http\Controllers\LineasLubricanteController;
use App\Http\Controllers\LineasNeumaticoController;
use App\Http\Controllers\LineasOtroAgregadoController;
use App\Http\Controllers\LocalesElectricosController;
use App\Http\Controllers\MotivosBajaBateriaController;
use App\Http\Controllers\MotivosEntradaTallerController;
use App\Http\Controllers\MovimientosInventarioController;
use App\Http\Controllers\PagosAdicionalesCargoController;
use App\Http\Controllers\RegistroOrdenesTallerController;
use App\Http\Controllers\TarjeteroController;
use App\Http\Controllers\TiposAceitesController;
use App\Http\Controllers\TiposArticulosBolsaController;
use App\Http\Controllers\TiposEntidadController;
use App\Http\Controllers\TiposJefeGrupoController;
use App\Http\Controllers\TiposRamasController;
use App\Http\Controllers\TiposRoturasController;
use App\Http\Controllers\TiposSistemasController;
use App\Http\Controllers\TiposSistemasCucController;
use App\Http\Controllers\TiposSubctaUnidadController;
use App\Http\Controllers\TiposSuspensionController;
use App\Http\Controllers\UnidadesController;
use App\Http\Controllers\VacacionesController;

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

        Route::resource('clientes-seleccion', ClientesSeleccionController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('turnos-comerciales', TurnosComercialesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('movil-web', MovilWebController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('alertas', AlertasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('indicadores', IndicadoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('demandas', DemandasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('pizarra-tractivos', PizarraTractivosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tarifas', TarifasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

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
        Route::resource('tipos-calificadores', TiposCalificadoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-causas-laborales', TiposCausasLaboralesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-causas-baja', TiposCausasBajaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-causas-movimiento', TiposCausasMovimientoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-clasificacion-laboral', TiposClasificacionLaboralController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-color-piel', TiposColorPielController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-deducciones', TiposDeduccionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-especialidad', TiposEspecialidadController::class)
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

        Route::resource('tipos-plantillas', TiposPlantillasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-sexo', TiposSexoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-tallas', TiposTallasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-ubicacion-defensa', TiposUbicacionDefensaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('perfiles-rh', PerfilesRhController::class)
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

        Route::resource('tipos-agregados', TipoAgregadosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('tipos-neumaticos', TipoNeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('posiciones-neumaticos', PosicionesNeumaticosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('embalajes', EmbalajesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('buques', BuquesController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('navieras', NavierasController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('organismos', OrganismosController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('categorias-cargo', CategoriasCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::resource('grupos-escala', GruposEscalaController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Reportes PDF/Excel (Fase 6)
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('marcas', [ReportController::class, 'pdfMarcas'])->name('marcas');
            Route::get('modelos', [ReportController::class, 'pdfModelos'])->name('modelos');
            Route::get('paises', [ReportController::class, 'pdfPaises'])->name('paises');
            Route::get('salario-prenomina', [ReportController::class, 'pdfSalarioPrenomina'])->name('salario-prenomina');
            Route::get('salario-choferes', [ReportController::class, 'pdfSalarioChoferes'])->name('salario-choferes');
            Route::get('factura/{factura}', [ReportController::class, 'pdfFactura'])->name('factura');
            Route::get('prefactura/{prefactura}', [ReportController::class, 'pdfPrefactura'])->name('prefactura');
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
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('balances-electricos', BalancesElectricosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('hotkeys', HotkeysController::class)
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
        Route::resource('locales-electricos', LocalesElectricosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('acciones-hotkeys', AccionesHotkeysController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // ATM - Inventario/Tarjetero (Fase 5.8)
        Route::resource('tarjetero', TarjeteroController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('lineas-bateria', LineasBateriaController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('lineas-diferencial', LineasDiferencialController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('lineas-lubricante', LineasLubricanteController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('lineas-neumatico', LineasNeumaticoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('lineas-otro-agregado', LineasOtroAgregadoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('movimientos-inventario', MovimientosInventarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('detalle-movimientos-inventario', DetalleMovimientosInventarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('detalle-vales-inventario', DetalleValesInventarioController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // RRHH - Tablas faltantes (Fase 5.8)
        Route::resource('centros-costos', CentrosCostosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-articulos-bolsa', TiposArticulosBolsaController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('competencias-cargo', CompetenciasCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('funciones-cargo', FuncionesCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-jefe-grupo', TiposJefeGrupoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('pagos-adicionales-cargo', PagosAdicionalesCargoController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-ramas', TiposRamasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-sistemas-cuc', TiposSistemasCucController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Comercial - Tablas faltantes (Fase 5.8)
        Route::resource('unidades', UnidadesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('contenedores', ContenedoresController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categorias-productos', CategoriasProductosController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-subcta-unidad', TiposSubctaUnidadController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Misc - Tablas varias (Fase 5.8)
        Route::resource('clientes-mm', ClientesMmController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-aceites', TiposAceitesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('tipos-entidad', TiposEntidadController::class)
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
        Route::resource('causas-gps', CausasGpsController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('causas-multas', CausasMultasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('importes-gps', ImportesGpsController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('importes-multas', ImportesMultasController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('vacaciones', VacacionesController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('estadisticas-explotacion', EstadisticasExplotacionController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('registro-ordenes-taller', RegistroOrdenesTallerController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::resource('detalle-prefacturas', DetallePrefacturasController::class)
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

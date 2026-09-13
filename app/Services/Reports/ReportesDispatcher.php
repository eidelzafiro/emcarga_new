<?php

namespace App\Services\Reports;

use App\Models\ReporteLegacy;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase A: enruta un reporte legacy (por id) a su handler Zafiro.
 * Mapa incremental por agrupación; los no implementados devuelven 404 claro.
 */
class ReportesDispatcher
{
    /** ids de reportes del grupo EXPORTAR TABLAS (se procesan en cola, R-3). */
    public const EXPORT_IDS = [1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082];

    /** idreporte => [clase servicio, método] */
    private const MAPA = [
        // INDICADORES (14)
        1 => [IndicadoresReportService::class, 'cargasResumen'],
        2 => [IndicadoresReportService::class, 'cviajesResumen'],
        23 => [CombustibleReportService::class, 'conciliacionCombustible'],
        27 => [IndicadoresReportService::class, 'gpsMovilweb'],
        28 => [CombustibleReportService::class, 'indicadoresDiferencias'],
        29 => [IndicadoresReportService::class, 'indicadoresResumen'],
        38 => [IndicadoresReportService::class, 'excel249'],
        141 => [IndicadoresReportService::class, 'combustibleConciliacion2'],
        143 => [IndicadoresReportService::class, 'excelResumenCp'],
        380 => [IndicadoresReportService::class, 'indicadoresOrigenes'],
        1008 => [IndicadoresReportService::class, 'bc4'],
        1009 => [IndicadoresReportService::class, 'bc2'],
        1022 => [IndicadoresReportService::class, 'excelResumenCpChofer'],
        1043 => [IndicadoresReportService::class, 'indicadoresNelson'],
        // GPS (5)
        21 => [GpsReportService::class, 'gpsConsejillo'],
        22 => [GpsReportService::class, 'gpsReporte'],
        24 => [GpsReportService::class, 'gpsIndice'],
        25 => [GpsReportService::class, 'gpsAnexo1Todas'],
        26 => [GpsReportService::class, 'gpsAnexo1'],
        // INGRESOS (10)
        30 => [IngresosReportService::class, 'ingresosDetalle'],
        31 => [IngresosReportService::class, 'devolucionesResumen'],
        32 => [IngresosReportService::class, 'ingresosResumen'],
        33 => [IngresosReportService::class, 'ingresosResumenChoferes'],
        60 => [IngresosReportService::class, 'ingresosConciliacion'],
        61 => [IngresosReportService::class, 'devolucionesChoferes'],
        62 => [IngresosReportService::class, 'devolucionesClientes'],
        64 => [IngresosReportService::class, 'devolucionesTractivos'],
        150 => [IngresosReportService::class, 'ingresosDemora'],
        4061 => [IngresosReportService::class, 'ingresosClientesSeleccionados'],
        // PIZARRA (6)
        34 => [PizarraReportService::class, 'planCarga'],
        35 => [PizarraReportService::class, 'pizarraOtras'],
        36 => [PizarraReportService::class, 'pizarraTransportacion'],
        37 => [PizarraReportService::class, 'pizarra'],
        135 => [PizarraReportService::class, 'pizarraResumen'],
        370 => [PizarraReportService::class, 'planCargaResumen'],
        // ADMINISTRACION (21)
        96 => [AdministracionReportService::class, 'combustibleParte'],
        97 => [AdministracionReportService::class, 'toneladasClientes'],
        101 => [AdministracionReportService::class, 'cpEstado'],
        102 => [AdministracionReportService::class, 'cpEstado'],
        103 => [AdministracionReportService::class, 'combustibleGrupo'],
        104 => [AdministracionReportService::class, 'combustibleResumen'],
        105 => [AdministracionReportService::class, 'indicesConsumo'],
        106 => [AdministracionReportService::class, 'ingresosChoferes'],
        107 => [AdministracionReportService::class, 'ingresosTractivos'],
        108 => [AdministracionReportService::class, 'indicadoresTractivos'],
        109 => [AdministracionReportService::class, 'cumplimientoPlanCarga'],
        110 => [AdministracionReportService::class, 'balanceCargaChoferes'],
        111 => [AdministracionReportService::class, 'planCombustibleDia'],
        112 => [AdministracionReportService::class, 'indicadoresTractivos'],
        117 => [AdministracionReportService::class, 'estadoSituacion'],
        134 => [AdministracionReportService::class, 'tarjetas3Dias'],
        378 => [AdministracionReportService::class, 'gpsConsejilloAdmin'],
        379 => [AdministracionReportService::class, 'tarjetasResponsable'],
        1024 => [AdministracionReportService::class, 'validacionOnure'],
        1025 => [AdministracionReportService::class, 'planToneladasChofer'],
        1032 => [AdministracionReportService::class, 'validacionOnureEquipos'],
        // COMBUSTIBLE (21)
        39 => [CombustibleReportService::class, 'cargasResumen'],
        40 => [CombustibleReportService::class, 'submayorTodas'],
        41 => [CombustibleReportService::class, 'descargasTecnica'],
        42 => [CombustibleReportService::class, 'submayorDetalle'],
        43 => [CombustibleReportService::class, 'tarjetasConSaldo'],
        44 => [CombustibleReportService::class, 'tarjetasSinSaldo'],
        45 => [CombustibleReportService::class, 'tarjetasSinSaldo'],
        46 => [CombustibleReportService::class, 'tarjetasResponsable'],
        113 => [CombustibleReportService::class, 'descargasVariables'],
        114 => [CombustibleReportService::class, 'descargasGrupo'],
        126 => [CombustibleReportService::class, 'tarjetasResumen'],
        133 => [CombustibleReportService::class, 'tarjetas3Dias'],
        377 => [CombustibleReportService::class, 'parteExistencias'],
        1001 => [CombustibleReportService::class, 'tarjetasVencimiento'],
        1011 => [CombustibleReportService::class, 'tractivosTanque'],
        1012 => [CombustibleReportService::class, 'descargasSuperior'],
        1013 => [CombustibleReportService::class, 'descargasEquiposTarjeta'],
        1020 => [CombustibleReportService::class, 'onureTodas'],
        1021 => [CombustibleReportService::class, 'onureDetalle'],
        1023 => [CombustibleReportService::class, 'validacion'],
        1031 => [CombustibleReportService::class, 'validacionEquipos'],
        // COSTOS (12)
        47 => [CostosReportService::class, 'dietasVariables'],
        48 => [CostosReportService::class, 'monedaExtranjera'],
        49 => [CostosReportService::class, 'monedaNacional'],
        50 => [CostosReportService::class, 'materialVariables'],
        51 => [CostosReportService::class, 'cuadreCuentas'],
        52 => [CostosReportService::class, 'relacionEquipos'],
        53 => [CostosReportService::class, 'dietasFolioCaja'],
        54 => [CostosReportService::class, 'dietasFolioEmision'],
        55 => [CostosReportService::class, 'amortizacionChapa'],
        56 => [CostosReportService::class, 'indirectosAdmin'],
        57 => [CostosReportService::class, 'indirectosTaller'],
        58 => [CostosReportService::class, 'gastosVariables'],
        // NOMINA / RRHH: se sirven por rutas dedicadas (ReportController:
        // reportes.salarios, reportes.prenomina-*, reportes.incidencias, etc.),
        // NO por el dispatcher genérico. Se retiraron del MAPA los mapeos de la
        // Fase C/D cuyos métodos nunca se implementaron en NominaReportService
        // (y cuya firma esperaba Request, no array): antes producían 500, ahora
        // devuelven el 404 "no migrado" del dispatcher.
        1059 => [TecnicaReportService::class, 'operacionesTallerOperarios'],
        4025 => [TecnicaAgrupadaService::class, 'certificoIndicesConsumo'],
        // TECNICA (10)
        138 => [TecnicaAgrupadaService::class, 'informeEstadoParque'],
        139 => [TecnicaAgrupadaService::class, 'informeAnualTractivo'],
        140 => [TecnicaAgrupadaService::class, 'situacionParque'],
        144 => [TecnicaAgrupadaService::class, 'controlCdt'],
        155 => [TecnicaAgrupadaService::class, 'disponibilidadKms'],
        161 => [TecnicaAgrupadaService::class, 'indicesDeteriorados'],
        366 => [TecnicaAgrupadaService::class, 'disponibilidadVayas'],
        367 => [TecnicaAgrupadaService::class, 'operacionesTallerOperarios'],
        369 => [TecnicaAgrupadaService::class, 'gastoLubricantes'],
        376 => [TecnicaAgrupadaService::class, 'conciliacionDisponibilidad'],
        // BATERIAS (3)
        145 => [TecnicaAgrupadaService::class, 'controlActivasXTractivos'],
        151 => [TecnicaAgrupadaService::class, 'planBajas'],
        152 => [TecnicaAgrupadaService::class, 'informacionBaterias'],
        // ENERGIA (5)
        360 => [EnergiaReportService::class, 'consumoElectrico'],
        362 => [EnergiaReportService::class, 'consumoAgua'],
        363 => [EnergiaReportService::class, 'consumoGas'],
        364 => [EnergiaReportService::class, 'controlIncidencias'],
        368 => [EnergiaReportService::class, 'portadoresEnergeticos'],
        // CONTROL TALLER (12)
        137 => [TecnicaAgrupadaService::class, 'ct2MttoEventuales'],
        142 => [TecnicaAgrupadaService::class, 'ct5MovimientoMes'],
        146 => [TecnicaAgrupadaService::class, 'ct8VidaUtilBateria'],
        147 => [TecnicaAgrupadaService::class, 'ct3TiempoAgregados'],
        148 => [TecnicaAgrupadaService::class, 'ct1Expediente'],
        149 => [TecnicaAgrupadaService::class, 'ct7ControlLubricantes'],
        159 => [TecnicaAgrupadaService::class, 'ct6AnalisisMotores'],
        160 => [TecnicaAgrupadaService::class, 'ct4ReparacionMantenimiento'],
        163 => [TecnicaAgrupadaService::class, 'ct5MovimientoFecha'],
        343 => [TecnicaAgrupadaService::class, 'datosGeneralesParque'],
        344 => [TecnicaAgrupadaService::class, 'crtCirculacion'],
        361 => [TecnicaAgrupadaService::class, 'mttosExterior'],
        // NEUMATICOS (6)
        153 => [TecnicaAgrupadaService::class, 'planBajasRecauches'],
        154 => [TecnicaAgrupadaService::class, 'informacionNeumaticos'],
        156 => [TecnicaAgrupadaService::class, 'cn2Neumatico'],
        162 => [TecnicaAgrupadaService::class, 'cn3AnalisisBaja'],
        365 => [TecnicaAgrupadaService::class, 'listadoBaja'],
        1000 => [TecnicaAgrupadaService::class, 'activosXTractivos'],
        // EXISTENCIA (1)
        374 => [ExistenciaReportService::class, 'extraccionContenedoresTipo'],
        // DOCUMENTOS (13) — Cartas de Porte y Hojas de Ruta (controles, parte diario, consecutivo, canceladas, registros)
        3 => [DocumentosReportService::class, 'cartaPorteCanceladas'],
        4 => [DocumentosReportService::class, 'cartaPorteConsecutivo'],
        5 => [DocumentosReportService::class, 'cartaPorteControlEstado'],
        6 => [DocumentosReportService::class, 'cartaPorteParteDiarioEmision'],
        7 => [DocumentosReportService::class, 'cartaPorteParteDiarioRecepcion'],
        157 => [DocumentosReportService::class, 'cartaPorteRegistroRes2132019'],
        8 => [DocumentosReportService::class, 'hojaRutaCanceladas'],
        9 => [DocumentosReportService::class, 'hojaRutaConsecutivo'],
        10 => [DocumentosReportService::class, 'hojaRutaControlEstado'],
        11 => [DocumentosReportService::class, 'hojaRutaParteDiarioCierre'],
        12 => [DocumentosReportService::class, 'hojaRutaParteDiarioEmision'],
        158 => [DocumentosReportService::class, 'hojaRutaRegistroRes184'],
        1014 => [DocumentosReportService::class, 'hojaRutaAnalisisDocumentacion'],
        // FACTURACION (10) — facturas, cartas a facturar, resúmenes, firmas, conciliaciones
        13 => [FacturacionAgrupadaService::class, 'facturasImpresion'],
        14 => [FacturacionAgrupadaService::class, 'cartasPorteAFacturar'],
        16 => [FacturacionAgrupadaService::class, 'registroFacturacionMes'],
        17 => [FacturacionAgrupadaService::class, 'resumenMensualClientes'],
        18 => [FacturacionAgrupadaService::class, 'resumenMensualOrganismos'],
        59 => [FacturacionAgrupadaService::class, 'impresionComprobantes'],
        1003 => [FacturacionAgrupadaService::class, 'facturasFirmadasClientes'],
        1005 => [FacturacionAgrupadaService::class, 'facturasPendientesFirma'],
        1007 => [FacturacionAgrupadaService::class, 'listadoConciliaciones'],
        4069 => [FacturacionAgrupadaService::class, 'resumenMensualClientesOtrasVentas'],
        // OTROS / RRHH (6) — plazas vacantes, aseo tecnológico, cumpleaños, licencias, documentos
        93 => [OtrosReportService::class, 'plazasVacantes'],
        130 => [OtrosReportService::class, 'aseoTecnologico'],
        131 => [OtrosReportService::class, 'cumpleanosMes'],
        1038 => [OtrosReportService::class, 'personalConLicencia'],
        1039 => [OtrosReportService::class, 'documentosChoferes'],
        1041 => [OtrosReportService::class, 'modeloAseoTecnologico'],
        // TIEMPOS (3) — resúmenes de tiempos y conciliación HR–Tiempos
        128 => [TiemposReportService::class, 'resumenTiemposTractivos'],
        129 => [TiemposReportService::class, 'resumenTiemposEmpresa'],
        136 => [TiemposReportService::class, 'conciliacionHojaRutaTiempos'],
        // EXPORTAR TABLAS (8) — volcados CSV de tablas maestras
        1075 => [ExportarTablasReportService::class, 'clientes'],
        1076 => [ExportarTablasReportService::class, 'organismos'],
        1077 => [ExportarTablasReportService::class, 'lugares'],
        1078 => [ExportarTablasReportService::class, 'productos'],
        1079 => [ExportarTablasReportService::class, 'girado'],
        1080 => [ExportarTablasReportService::class, 'aforo'],
        1081 => [ExportarTablasReportService::class, 'hojaRutas'],
        1082 => [ExportarTablasReportService::class, 'tractivos'],
        // EMCARGA (5)
        4062 => [EmcargaReportService::class, 'distanciaMediaTonelada'],
        4063 => [EmcargaReportService::class, 'cargaTransportada'],
        4064 => [EmcargaReportService::class, 'traficoProducido'],
        4065 => [EmcargaReportService::class, 'kilometrosTotales'],
        4066 => [EmcargaReportService::class, 'kilometrosCarga'],
    ];

    public function generar(int $id, array $filtros): Response
    {
        if (! isset(self::MAPA[$id])) {
            $reporte = ReporteLegacy::find($id);
            abort(404, 'Reporte aún no migrado a Zafiro: '.($reporte?->nombreporte ?? $id));
        }

        [$clase, $metodo] = self::MAPA[$id];

        return app($clase)->{$metodo}($filtros);
    }

    /**
     * Indica si el reporte pertenece al grupo EXPORTAR TABLAS y debe
     * procesarse en cola (R-3) en vez de devolverse de forma síncrona.
     */
    public static function esExportacion(int $id): bool
    {
        return in_array($id, self::EXPORT_IDS, true);
    }

    /** Indica si el reporte tiene handler implementado en el dispatcher. */
    public static function estaMapeado(int $id): bool
    {
        return isset(self::MAPA[$id]);
    }
}

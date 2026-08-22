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
        1    => [IndicadoresReportService::class, 'cargasResumen'],
        2    => [IndicadoresReportService::class, 'cviajesResumen'],
        23   => [IndicadoresReportService::class, 'combustibleConciliacion'],
        27   => [IndicadoresReportService::class, 'gpsMovilweb'],
        28   => [IndicadoresReportService::class, 'indicadoresDiferencias'],
        29   => [IndicadoresReportService::class, 'indicadoresResumen'],
        38   => [IndicadoresReportService::class, 'excel249'],
        141  => [IndicadoresReportService::class, 'combustibleConciliacion2'],
        143  => [IndicadoresReportService::class, 'excelResumenCp'],
        380  => [IndicadoresReportService::class, 'indicadoresOrigenes'],
        1008 => [IndicadoresReportService::class, 'bc4'],
        1009 => [IndicadoresReportService::class, 'bc2'],
        1022 => [IndicadoresReportService::class, 'excelResumenCpChofer'],
        1043 => [IndicadoresReportService::class, 'indicadoresNelson'],
        // GPS (5)
        21   => [GpsReportService::class, 'gpsConsejillo'],
        22   => [GpsReportService::class, 'gpsReporte'],
        24   => [GpsReportService::class, 'gpsIndice'],
        25   => [GpsReportService::class, 'gpsAnexo1Todas'],
        26   => [GpsReportService::class, 'gpsAnexo1'],
        // INGRESOS (10)
        30   => [IngresosReportService::class, 'ingresosDetalle'],
        31   => [IngresosReportService::class, 'devolucionesResumen'],
        32   => [IngresosReportService::class, 'ingresosResumen'],
        33   => [IngresosReportService::class, 'ingresosResumenChoferes'],
        60   => [IngresosReportService::class, 'ingresosConciliacion'],
        61   => [IngresosReportService::class, 'devolucionesChoferes'],
        62   => [IngresosReportService::class, 'devolucionesClientes'],
        64   => [IngresosReportService::class, 'devolucionesTractivos'],
        150  => [IngresosReportService::class, 'ingresosDemora'],
        4061 => [IngresosReportService::class, 'ingresosClientesSeleccionados'],
        // PIZARRA (6)
        34   => [PizarraReportService::class, 'planCarga'],
        35   => [PizarraReportService::class, 'pizarraOtras'],
        36   => [PizarraReportService::class, 'pizarraTransportacion'],
        37   => [PizarraReportService::class, 'pizarra'],
        135  => [PizarraReportService::class, 'pizarraResumen'],
        370  => [PizarraReportService::class, 'planCargaResumen'],
        // ADMINISTRACION (21)
        96   => [AdministracionReportService::class, 'combustibleParte'],
        97   => [AdministracionReportService::class, 'toneladasClientes'],
        101  => [AdministracionReportService::class, 'cpEstado'],
        102  => [AdministracionReportService::class, 'cpEstado'],
        103  => [AdministracionReportService::class, 'combustibleGrupo'],
        104  => [AdministracionReportService::class, 'combustibleResumen'],
        105  => [AdministracionReportService::class, 'indicesConsumo'],
        106  => [AdministracionReportService::class, 'ingresosChoferes'],
        107  => [AdministracionReportService::class, 'ingresosTractivos'],
        108  => [AdministracionReportService::class, 'indicadoresTractivos'],
        109  => [AdministracionReportService::class, 'cumplimientoPlanCarga'],
        110  => [AdministracionReportService::class, 'balanceCargaChoferes'],
        111  => [AdministracionReportService::class, 'planCombustibleDia'],
        112  => [AdministracionReportService::class, 'indicadoresTractivos'],
        117  => [AdministracionReportService::class, 'estadoSituacion'],
        134  => [AdministracionReportService::class, 'tarjetas3Dias'],
        378  => [AdministracionReportService::class, 'gpsConsejilloAdmin'],
        379  => [AdministracionReportService::class, 'tarjetasResponsable'],
        1024 => [AdministracionReportService::class, 'validacionOnure'],
        1025 => [AdministracionReportService::class, 'planToneladasChofer'],
        1032 => [AdministracionReportService::class, 'validacionOnureEquipos'],
        // COMBUSTIBLE (21)
        39   => [CombustibleReportService::class, 'cargasResumen'],
        40   => [CombustibleReportService::class, 'submayor'],
        41   => [CombustibleReportService::class, 'descargasTecnica'],
        42   => [CombustibleReportService::class, 'submayor'],
        43   => [CombustibleReportService::class, 'tarjetasConSaldo'],
        44   => [CombustibleReportService::class, 'tarjetasSinSaldo'],
        45   => [CombustibleReportService::class, 'tarjetasSinSaldo'],
        46   => [CombustibleReportService::class, 'tarjetasResponsable'],
        113  => [CombustibleReportService::class, 'descargasVariables'],
        114  => [CombustibleReportService::class, 'descargasGrupo'],
        126  => [CombustibleReportService::class, 'tarjetasResumen'],
        133  => [CombustibleReportService::class, 'tarjetas3Dias'],
        377  => [CombustibleReportService::class, 'parteExistencias'],
        1001 => [CombustibleReportService::class, 'tarjetasVencimiento'],
        1011 => [CombustibleReportService::class, 'tractivosTanque'],
        1012 => [CombustibleReportService::class, 'descargasSuperior'],
        1013 => [CombustibleReportService::class, 'descargasEquiposTarjeta'],
        1020 => [CombustibleReportService::class, 'onureTodas'],
        1021 => [CombustibleReportService::class, 'onureDetalle'],
        1023 => [CombustibleReportService::class, 'validacion'],
        1031 => [CombustibleReportService::class, 'validacionEquipos'],
        // COSTOS (12)
        47   => [CostosReportService::class, 'dietasVariables'],
        48   => [CostosReportService::class, 'monedaExtranjera'],
        49   => [CostosReportService::class, 'monedaNacional'],
        50   => [CostosReportService::class, 'materialVariables'],
        51   => [CostosReportService::class, 'cuadreCuentas'],
        52   => [CostosReportService::class, 'relacionEquipos'],
        53   => [CostosReportService::class, 'dietasFolioCaja'],
        54   => [CostosReportService::class, 'dietasFolioEmision'],
        55   => [CostosReportService::class, 'amortizacionChapa'],
        56   => [CostosReportService::class, 'indirectosAdmin'],
        57   => [CostosReportService::class, 'indirectosTaller'],
        58   => [CostosReportService::class, 'gastosVariables'],
        // NOMINA (33)
        79   => [NominaReportService::class, 'incidenciasTiempoTrabajado'],
        80   => [NominaReportService::class, 'penalizacionesPagoAdicional'],
        87   => [NominaReportService::class, 'datosTrabajadores'],
        88   => [NominaReportService::class, 'resumenIncidencias'],
        90   => [NominaReportService::class, 'pagosNocturnidad'],
        91   => [NominaReportService::class, 'salariosSistemaPago'],
        92   => [NominaReportService::class, 'controlDiarioTrabajo'],
        1046 => [NominaReportService::class, 'exportarPrenomina'],
        1066 => [NominaReportService::class, 'resumenIncidencias'],
        4067 => [NominaReportService::class, 'salariosResultados'],
        4068 => [NominaReportService::class, 'salariosResultados'],
        119  => [NominaReportService::class, 'analisisIndicadoresSalarios'],
        120  => [NominaReportService::class, 'analisisTiempos'],
        121  => [NominaReportService::class, 'modelo1Control'],
        122  => [NominaReportService::class, 'modelo1ControlDetalle'],
        123  => [NominaReportService::class, 'listadoGarantiaSalarial'],
        124  => [NominaReportService::class, 'pagoGarantiaSalarial'],
        125  => [NominaReportService::class, 'pagoSalarios'],
        1047 => [NominaReportService::class, 'controlDiarioChoferes'],
        1074 => [NominaReportService::class, 'exportarPrenominaChoferes'],
        1048 => [NominaReportService::class, 'certificacionComercial'],
        1055 => [NominaReportService::class, 'resumenGastosDietas'],
        1056 => [NominaReportService::class, 'resumenIndicadoresExplotacion'],
        1057 => [NominaReportService::class, 'resumenIngresosChoferes'],
        1059 => [NominaReportService::class, 'operacionesTallerOperarios'],
        1060 => [NominaReportService::class, 'certificacionGastosEquipo'],
        1062 => [NominaReportService::class, 'certificacionCumplimientoCdt'],
        1064 => [NominaReportService::class, 'certificacionAlmacenamiento'],
        1068 => [NominaReportService::class, 'certificacionComercial'],
        1072 => [NominaReportService::class, 'certificacionComercialTonKm'],
        1073 => [NominaReportService::class, 'certificacionComercialTonKm'],
        4025 => [NominaReportService::class, 'certificoIndicesConsumo'],
        4060 => [NominaReportService::class, 'reporteOperacionesEmcarga'],
        // TECNICA (10)
        138  => [TecnicaReportService::class, 'informeEstadoParque'],
        139  => [TecnicaReportService::class, 'informeAnualTractivo'],
        140  => [TecnicaReportService::class, 'situacionParque'],
        144  => [TecnicaReportService::class, 'controlCdt'],
        155  => [TecnicaReportService::class, 'disponibilidadKms'],
        161  => [TecnicaReportService::class, 'indicesDeteriorados'],
        366  => [TecnicaReportService::class, 'disponibilidadVayas'],
        367  => [TecnicaReportService::class, 'operacionesTallerOperarios'],
        369  => [TecnicaReportService::class, 'gastoLubricantes'],
        376  => [TecnicaReportService::class, 'conciliacionDisponibilidad'],
        // BATERIAS (3)
        145  => [BateriasReportService::class, 'controlActivasXTractivos'],
        151  => [BateriasReportService::class, 'planBajas'],
        152  => [BateriasReportService::class, 'informacion'],
        // ENERGIA (5)
        360  => [EnergiaReportService::class, 'consumoElectrico'],
        362  => [EnergiaReportService::class, 'consumoAgua'],
        363  => [EnergiaReportService::class, 'consumoGas'],
        364  => [EnergiaReportService::class, 'controlIncidencias'],
        368  => [EnergiaReportService::class, 'portadoresEnergeticos'],
        // CONTROL TALLER (12)
        137  => [TallerReportService::class, 'ct2MttoEventuales'],
        142  => [TallerReportService::class, 'ct5MovimientoMes'],
        146  => [TallerReportService::class, 'ct8VidaUtilBateria'],
        147  => [TallerReportService::class, 'ct3TiempoAgregados'],
        148  => [TallerReportService::class, 'ct1Expediente'],
        149  => [TallerReportService::class, 'ct7ControlLubricantes'],
        159  => [TallerReportService::class, 'ct6AnalisisMotores'],
        160  => [TallerReportService::class, 'ct4ReparacionMantenimiento'],
        163  => [TallerReportService::class, 'ct5MovimientoFecha'],
        343  => [TallerReportService::class, 'datosGeneralesParque'],
        344  => [TallerReportService::class, 'crtCirculacion'],
        361  => [TallerReportService::class, 'mttosExterior'],
        // NEUMATICOS (6)
        153  => [NeumaticosReportService::class, 'planBajasRecauches'],
        154  => [NeumaticosReportService::class, 'informacion'],
        156  => [NeumaticosReportService::class, 'cn2Neumatico'],
        162  => [NeumaticosReportService::class, 'cn3AnalisisBaja'],
        365  => [NeumaticosReportService::class, 'listadoBaja'],
        1000 => [NeumaticosReportService::class, 'activosXTractivos'],
        // EXISTENCIA (1)
        374  => [ExistenciaReportService::class, 'extraccionContenedoresTipo'],
        // DOCUMENTOS (13) — Cartas de Porte y Hojas de Ruta (controles, parte diario, consecutivo, canceladas, registros)
        3    => [DocumentosReportService::class, 'cartaPorteCanceladas'],
        4    => [DocumentosReportService::class, 'cartaPorteConsecutivo'],
        5    => [DocumentosReportService::class, 'cartaPorteControlEstado'],
        6    => [DocumentosReportService::class, 'cartaPorteParteDiarioEmision'],
        7    => [DocumentosReportService::class, 'cartaPorteParteDiarioRecepcion'],
        157  => [DocumentosReportService::class, 'cartaPorteRegistroRes2132019'],
        8    => [DocumentosReportService::class, 'hojaRutaCanceladas'],
        9    => [DocumentosReportService::class, 'hojaRutaConsecutivo'],
        10   => [DocumentosReportService::class, 'hojaRutaControlEstado'],
        11   => [DocumentosReportService::class, 'hojaRutaParteDiarioCierre'],
        12   => [DocumentosReportService::class, 'hojaRutaParteDiarioEmision'],
        158  => [DocumentosReportService::class, 'hojaRutaRegistroRes184'],
        1014 => [DocumentosReportService::class, 'hojaRutaAnalisisDocumentacion'],
        // DOCUMENTOS (13) — Cartas de Porte y Hojas de Ruta (controles, parte diario, consecutivo, canceladas, registros)
        3    => [DocumentosReportService::class, 'cartaPorteCanceladas'],
        4    => [DocumentosReportService::class, 'cartaPorteConsecutivo'],
        5    => [DocumentosReportService::class, 'cartaPorteControlEstado'],
        6    => [DocumentosReportService::class, 'cartaPorteParteDiarioEmision'],
        7    => [DocumentosReportService::class, 'cartaPorteParteDiarioRecepcion'],
        157  => [DocumentosReportService::class, 'cartaPorteRegistroRes2132019'],
        8    => [DocumentosReportService::class, 'hojaRutaCanceladas'],
        9    => [DocumentosReportService::class, 'hojaRutaConsecutivo'],
        10   => [DocumentosReportService::class, 'hojaRutaControlEstado'],
        11   => [DocumentosReportService::class, 'hojaRutaParteDiarioCierre'],
        12   => [DocumentosReportService::class, 'hojaRutaParteDiarioEmision'],
        158  => [DocumentosReportService::class, 'hojaRutaRegistroRes184'],
        1014 => [DocumentosReportService::class, 'hojaRutaAnalisisDocumentacion'],
        // FACTURACION (10) — facturas, cartas a facturar, resúmenes, firmas, conciliaciones
        13   => [FacturacionReportService::class, 'facturasImpresion'],
        14   => [FacturacionReportService::class, 'cartasPorteAFacturar'],
        16   => [FacturacionReportService::class, 'registroFacturacionMes'],
        17   => [FacturacionReportService::class, 'resumenMensualClientes'],
        18   => [FacturacionReportService::class, 'resumenMensualOrganismos'],
        59   => [FacturacionReportService::class, 'impresionComprobantes'],
        1003 => [FacturacionReportService::class, 'facturasFirmadasClientes'],
        1005 => [FacturacionReportService::class, 'facturasPendientesFirma'],
        1007 => [FacturacionReportService::class, 'listadoConciliaciones'],
        4069 => [FacturacionReportService::class, 'resumenMensualClientesOtrasVentas'],
        // OTROS / RRHH (6) — plazas vacantes, aseo tecnológico, cumpleaños, licencias, documentos
        93   => [OtrosReportService::class, 'plazasVacantes'],
        130  => [OtrosReportService::class, 'aseoTecnologico'],
        131  => [OtrosReportService::class, 'cumpleanosMes'],
        1038 => [OtrosReportService::class, 'personalConLicencia'],
        1039 => [OtrosReportService::class, 'documentosChoferes'],
        1041 => [OtrosReportService::class, 'modeloAseoTecnologico'],
        // TIEMPOS (3) — resúmenes de tiempos y conciliación HR–Tiempos
        128  => [TiemposReportService::class, 'resumenTiemposTractivos'],
        129  => [TiemposReportService::class, 'resumenTiemposEmpresa'],
        136  => [TiemposReportService::class, 'conciliacionHojaRutaTiempos'],
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
}

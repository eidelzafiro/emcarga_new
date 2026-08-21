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
}

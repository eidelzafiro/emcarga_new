<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\AforoIndicadore;
use App\Models\CartaPorte;
use App\Models\Tractivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — INDICADORES (14 reportes legacy).
 *
 * Versiones funcionales sobre las tablas ya migradas (aforos, cartas_porte,
 * aforo_indicadores, tractivos). Donde el reporte legacy agrupaba por
 * origen/destino/chófer (columnas que no se migraron a Zafiro), se agrupa por
 * la dimensión disponible (mes, tipo_indicadores, tractivo). Paridad de fórmulas
 * legacy pendiente de revisión fino.
 */
class IndicadoresReportService extends BaseReportService
{
    protected function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) {
            return null;
        }
        try {
            return Carbon::parse($filtros['mes'])->format('Y-m');
        } catch (\Exception) {
            return null;
        }
    }

    private function queryAforos(?string $mes)
    {
        $q = Aforo::query()->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id');
        if ($mes) {
            $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes);
        }

        return $q;
    }

    // 1 · ANALISIS CARGAS X RANGO DE KMS
    public function cargasResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.viajes) as viajes,
                SUM(aforos.tn_real_total) as toneladas,
                SUM(aforos.km_carga_total) as km_carga,
                SUM(aforos.km_vacio_total) as km_vacio,
                SUM(aforos.km_total_total) as km_total,
                SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'km_carga', 'label' => 'Km Carga', 'num' => true],
            ['key' => 'km_vacio', 'label' => 'Km Vacío', 'num' => true],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        return $this->reporteTablaPdf('Análisis Cargas', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }

    // 2 · ANALISIS CARGAS X ORIGEN
    public function cviajesResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.viajes) as viajes,
                SUM(aforos.tn_pos_total) as tn_pos,
                SUM(aforos.tn_real_total) as tn_real,
                SUM(aforos.traf_real_total) as traf_real,
                SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
            ['key' => 'tn_pos', 'label' => 'Tn Pos', 'num' => true],
            ['key' => 'tn_real', 'label' => 'Tn Real', 'num' => true],
            ['key' => 'traf_real', 'label' => 'Traf Real', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        return $this->reporteTablaPdf('Análisis Cargas por Origen', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }

    // 23 · CONCILIACION HOJA RUTA - CONTABILIDAD
    public function combustibleConciliacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.km_total_total) as km_total,
                SUM(aforos.salario) as salario,
                SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        return $this->reporteTablaPdf('Conciliación Hoja Ruta - Contabilidad', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses']);
    }

    // 27 · REPORTE MOVILWEB (GPS)
    public function gpsMovilweb(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tractivo::query()
            ->select('codigo as tractivo', 'estado')
            ->orderBy('codigo')->limit(500)->get();

        $columnas = [
            ['key' => 'tractivo', 'label' => 'Tractivo'],
            ['key' => 'estado', 'label' => 'Estado'],
        ];

        return $this->reporteTablaPdf('Reporte Móvil Web (GPS)', $columnas, $rows->toArray(),
            ['periodo' => 'Posiciones GPS no migradas a Zafiro; se listan tractivos activos', 'landscape' => true]);
    }

    // 28 · CONCILIACION HOJA RUTA - INDICADORES (diferencias)
    public function indicadoresDiferencias(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('aforo_indicadores', 'aforos.id', '=', 'aforo_indicadores.id_aforo')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.km_total_total) as km_aforo,
                SUM(aforo_indicadores.km_total) as km_indicador,
                SUM(aforos.km_total_total - aforo_indicadores.km_total) as diferencia")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'km_aforo', 'label' => 'Km Aforo', 'num' => true],
            ['key' => 'km_indicador', 'label' => 'Km Indicador', 'num' => true],
            ['key' => 'diferencia', 'label' => 'Diferencia', 'num' => true],
        ];

        return $this->reporteTablaPdf('Conciliación Hoja Ruta - Indicadores', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }

    // 29 · RESUMEN INDICADORES X VARIABLES
    public function indicadoresResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tipo_indicadores,'') as tipo,
                SUM(viajes) as viajes,
                SUM(tn_pos_total) as tn_pos,
                SUM(tn_real_total) as tn_real,
                SUM(traf_real_total) as traf_real,
                SUM(ingreso_mt) as ingreso_mt")
            ->groupBy('tipo_indicadores')->orderBy('tipo')->get();

        $columnas = [
            ['key' => 'tipo', 'label' => 'Tipo Indicador'],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
            ['key' => 'tn_pos', 'label' => 'Tn Pos', 'num' => true],
            ['key' => 'tn_real', 'label' => 'Tn Real', 'num' => true],
            ['key' => 'traf_real', 'label' => 'Traf Real', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        return $this->reporteTablaPdf('Resumen Indicadores por Variables', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }

    // 38 · ANALISIS TRANSPORTACIONES RES 249 (EXCEL)
    public function excel249(array $filtros): \Symfony\Component\HttpFoundation\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("aforos.id, cartas_porte.numero as cp, aforos.fecha_parte,
                aforos.viajes, aforos.tn_real_total as toneladas,
                aforos.km_total_total as km_total, aforos.ingreso_mt as ingreso_mt,
                aforos.salario")
            ->orderBy('aforos.fecha_parte')->get();

        $columnas = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'cp', 'label' => 'CP'],
            ['key' => 'fecha_parte', 'label' => 'Fecha Parte'],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
        ];

        return $this->reporteTablaExcel('Análisis Transportaciones RES 249', $columnas, $rows->toArray());
    }

    // 141 · CONCILIACION CHOFER HOJA RUTA - CHIP COMBUSTIBLE
    public function combustibleConciliacion2(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.km_total_total) as km_total,
                SUM(aforos.salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
        ];

        return $this->reporteTablaPdf('Conciliación Chófer Hoja Ruta - Chip Combustible', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses']);
    }

    // 143 · RESUMEN CP PARA EXCEL
    public function excelResumenCp(array $filtros): \Symfony\Component\HttpFoundation\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("cartas_porte.numero as cp, cartas_porte.fecha_emision,
                cartas_porte.toneladas, cartas_porte.distancia,
                aforos.ingreso_mt, aforos.salario")
            ->orderBy('cartas_porte.fecha_emision')->get();

        $columnas = [
            ['key' => 'cp', 'label' => 'CP'],
            ['key' => 'fecha_emision', 'label' => 'Emisión'],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'distancia', 'label' => 'Distancia', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
        ];

        return $this->reporteTablaExcel('Resumen CP', $columnas, $rows->toArray());
    }

    // 380 · RESUMEN INDICADORES ORIGENES-DESTINO-PRODUCTOS
    public function indicadoresOrigenes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.tn_real_total) as toneladas,
                SUM(aforos.km_total_total) as km_total,
                SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
        ];

        return $this->reporteTablaPdf('Resumen Indicadores Orígenes-Destino-Productos', $columnas, $rows->toArray(),
            ['periodo' => 'Origen/Destino/Producto no migrados; agrupado por mes', 'landscape' => true]);
    }

    // 1008 · MODELO BC-4 PLAN TRANSPORTACION POR ORIGEN Y DESTINO CARGAS
    public function bc4(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.tn_real_total) as toneladas,
                SUM(aforos.km_total_total) as km_total")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
        ];

        return $this->reporteTablaPdf('Modelo BC-4 Plan Transporte Origen-Destino', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }

    // 1009 · MODELO BC-2 DEMANDA TRANSPORTACION DE CARGAS
    public function bc2(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m') as mes,
                SUM(aforos.tn_real_total) as demanda_tn,
                SUM(aforos.viajes) as viajes")
            ->groupBy(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"))
            ->orderBy('mes')->get();

        $columnas = [
            ['key' => 'mes', 'label' => 'Mes'],
            ['key' => 'demanda_tn', 'label' => 'Demanda (Tn)', 'num' => true],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
        ];

        return $this->reporteTablaPdf('Modelo BC-2 Demanda Transporte de Cargas', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses']);
    }

    // 1022 · RESUMEN CP PARA EXCEL DETALLE CHOFER
    public function excelResumenCpChofer(array $filtros): \Symfony\Component\HttpFoundation\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = $this->queryAforos($mes)
            ->selectRaw("aforos.id, cartas_porte.numero as cp, aforos.fecha_parte,
                aforos.salario, aforos.ingreso_mt, aforos.tn_real_total as toneladas")
            ->orderBy('aforos.fecha_parte')->get();

        $columnas = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'cp', 'label' => 'CP'],
            ['key' => 'fecha_parte', 'label' => 'Fecha Parte'],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
        ];

        return $this->reporteTablaExcel('Resumen CP Detalle Chófer', $columnas, $rows->toArray());
    }

    // 1043 · DETALLE INDICADORES TRACTIVOS
    public function indicadoresNelson(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo,
                SUM(aforos.viajes) as viajes,
                SUM(aforos.tn_real_total) as toneladas,
                SUM(aforos.km_total_total) as km_total,
                SUM(aforos.ingreso_mt) as ingreso_mt,
                SUM(aforos.salario) as salario")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        $columnas = [
            ['key' => 'tractivo', 'label' => 'Tractivo'],
            ['key' => 'viajes', 'label' => 'Viajes', 'num' => true],
            ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ['key' => 'km_total', 'label' => 'Km Total', 'num' => true],
            ['key' => 'ingreso_mt', 'label' => 'Ingreso MT', 'num' => true],
            ['key' => 'salario', 'label' => 'Salario', 'num' => true],
        ];

        return $this->reporteTablaPdf('Detalle Indicadores Tractivos', $columnas, $rows->toArray(),
            ['periodo' => $mes ? 'Mes: '.$mes : 'Todos los meses', 'landscape' => true]);
    }
}

<?php

namespace App\Services\Reports;

use App\Models\Dieta;
use App\Models\IndirectoMensual;
use App\Models\ReporteCosto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase B — COSTOS (12 reportes legacy). Versiones funcionales sobre
 * reportes_costos (calculado por CostoCalculoService), dietas e
 * indirectos_mensuales.
 */
class CostosReportService extends BaseReportService
{
    protected function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) return null;
        try { return Carbon::parse($filtros['mes'])->format('Y-m'); } catch (\Exception) { return null; }
    }

    protected function rangoFiltros(array $filtros): array
    {
        $d = $filtros['desde'] ?? null;
        $h = $filtros['hasta'] ?? null;
        if (! $d && ! $h && ! empty($filtros['mes'])) {
            try { $m = Carbon::parse($filtros['mes']); $d = $m->copy()->startOfMonth()->toDateString(); $h = $m->copy()->endOfMonth()->toDateString(); } catch (\Exception) {}
        }
        return [$d, $h];
    }

    // 47 · RESUMEN GASTOS DIETAS POR VARIABLES
    public function dietasVariables(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Dieta::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tipo_dieta,'') as variable, SUM(monto) as monto")
            ->groupBy('tipo_dieta')->orderBy('tipo_dieta')->get();
        return $this->reporteTablaPdf('Resumen Gastos Dietas por Variables',
            [['key'=>'variable','label'=>'Variable'],['key'=>'monto','label'=>'Monto','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 48 · RESUMEN GASTOS EN MONEDA EXTRANJERA
    public function monedaExtranjera(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(utilidad_mlc) as mlc")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Gastos en Moneda Extranjera',
            [['key'=>'mes','label'=>'Mes'],['key'=>'mlc','label'=>'MLC','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 49 · RESUMEN GASTOS EN MONEDA NACIONAL
    public function monedaNacional(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(gastos_mn) as gastos_mn, SUM(ingresos_mn) as ingresos_mn")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Gastos en Moneda Nacional',
            [['key'=>'mes','label'=>'Mes'],['key'=>'gastos_mn','label'=>'Gastos MN','num'=>true],['key'=>'ingresos_mn','label'=>'Ingresos MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 50 · RESUMEN GASTOS MATERIAL POR VARIABLES
    public function materialVariables(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(piezas_mn + lubricante_mn) as material")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Gastos Material por Variables',
            [['key'=>'mes','label'=>'Mes'],['key'=>'material','label'=>'Material MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 51 · CUADRE CUENTAS CONTABILIDAD
    public function cuadreCuentas(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(ingresos_mn) as ingresos, SUM(gastos_mn) as gastos, SUM(utilidad_mn) as utilidad")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Cuadre Cuentas Contabilidad',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ingresos','label'=>'Ingresos','num'=>true],['key'=>'gastos','label'=>'Gastos','num'=>true],['key'=>'utilidad','label'=>'Utilidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 52 · RELACION GASTOS-INGRESOS POR EQUIPOS
    public function relacionEquipos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->leftJoin('tractivos', 'reportes_costos.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(reportes_costos.fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tractivos.codigo,'') as tractivo, SUM(reportes_costos.ingresos_mn) as ingresos, SUM(reportes_costos.gastos_mn) as gastos")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();
        return $this->reporteTablaPdf('Relación Gastos-Ingresos por Equipos',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'ingresos','label'=>'Ingresos','num'=>true],['key'=>'gastos','label'=>'Gastos','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 53 · DIETAS CONSECUTIVO X FOLIO CAJA
    public function dietasFolioCaja(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = Dieta::query()->whereNotNull('folio_caja');
        if ($d) $q->whereBetween('fecha', [$d, $h]);
        $rows = $q->selectRaw("folio_caja, SUM(monto) as monto, COUNT(*) as n")
            ->groupBy('folio_caja')->orderBy('folio_caja')->limit(800)->get();
        return $this->reporteTablaPdf('Dietas Consecutivo x Folio Caja',
            [['key'=>'folio_caja','label'=>'Folio Caja'],['key'=>'monto','label'=>'Monto','num'=>true],['key'=>'n','label'=>'N','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 54 · DIETAS CONSECUTIVO X FOLIO EMISION
    public function dietasFolioEmision(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = Dieta::query()->whereNotNull('f_liquidacion');
        if ($d) $q->whereBetween('f_liquidacion', [$d, $h]);
        $rows = $q->selectRaw("f_liquidacion as emision, folio_caja, SUM(monto) as monto")
            ->groupBy('f_liquidacion','folio_caja')->orderBy('f_liquidacion')->limit(800)->get();
        return $this->reporteTablaPdf('Dietas Consecutivo x Folio Emisión',
            [['key'=>'emision','label'=>'Emisión'],['key'=>'folio_caja','label'=>'Folio Caja'],['key'=>'monto','label'=>'Monto','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 55 · GASTOS AMORTIZACION-CHAPA
    public function amortizacionChapa(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(amortizacion_mn) as amortizacion, SUM(chapa) as chapa")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Gastos Amortización-Chapa',
            [['key'=>'mes','label'=>'Mes'],['key'=>'amortizacion','label'=>'Amortización MN','num'=>true],['key'=>'chapa','label'=>'Chapa MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 56 · GASTOS INDIRECTOS ADMINISTRACION
    public function indirectosAdmin(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = IndirectoMensual::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fcontabilidad,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fcontabilidad,'%Y-%m') as mes, SUM(indirectoadminmn) as admin")
            ->groupBy(DB::raw("DATE_FORMAT(fcontabilidad,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Gastos Indirectos Administración',
            [['key'=>'mes','label'=>'Mes'],['key'=>'admin','label'=>'Admin MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 57 · GASTOS INDIRECTOS TALLER
    public function indirectosTaller(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = IndirectoMensual::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fcontabilidad,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fcontabilidad,'%Y-%m') as mes, SUM(indirectotallermn) as taller")
            ->groupBy(DB::raw("DATE_FORMAT(fcontabilidad,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Gastos Indirectos Taller',
            [['key'=>'mes','label'=>'Mes'],['key'=>'taller','label'=>'Taller MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 58 · GASTOS VARIABLES
    public function gastosVariables(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ReporteCosto::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_reporte,'%Y-%m') as mes, SUM(gastos_mn) as gastos, SUM(otros_gastos_mn) as otros")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_reporte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Gastos Variables',
            [['key'=>'mes','label'=>'Mes'],['key'=>'gastos','label'=>'Gastos MN','num'=>true],['key'=>'otros','label'=>'Otros MN','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }
}

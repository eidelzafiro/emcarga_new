<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — INGRESOS (10 reportes legacy). Versiones funcionales sobre `aforos`
 * (ingreso_mt, descuentos, demora). Chofer/cliente puntual no migrados a
 * Zafiro en esas tablas; se agrupa por la dimensión disponible.
 */
class IngresosReportService extends BaseReportService
{
    private function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) return null;
        try { return Carbon::parse($filtros['mes'])->format('Y-m'); } catch (\Exception) { return null; }
    }

    // 30 · PARTE DIARIO CP AFORADAS
    public function ingresosDetalle(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = Aforo::query()->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id');
        if ($d) $q->whereBetween('aforos.fecha_parte', [$d, $h]);
        $rows = $q->selectRaw("aforos.id, cartas_porte.numero as cp, aforos.fecha_parte, aforos.ingreso_mt, aforos.salario")
            ->orderBy('aforos.fecha_parte')->limit(800)->get();

        return $this->reporteTablaPdf('Parte Diario CP Aforadas',
            [['key'=>'id','label'=>'ID'],['key'=>'cp','label'=>'CP'],['key'=>'fecha_parte','label'=>'Fecha Parte'],
             ['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 31 · RESUMEN DEVOLUCIONES X VARIABLES
    public function devolucionesResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tipo_indicadores,'') as tipo, SUM(descuento) as devolucion, COUNT(*) as n")
            ->groupBy('tipo_indicadores')->orderBy('tipo')->get();

        return $this->reporteTablaPdf('Resumen Devoluciones por Variables',
            [['key'=>'tipo','label'=>'Tipo'],['key'=>'devolucion','label'=>'Devolución','num'=>true],['key'=>'n','label'=>'N','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 32 · RESUMEN INGRESOS POR VARIABLES
    public function ingresosResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tipo_indicadores,'') as tipo, SUM(ingreso_mt) as ingreso_mt, SUM(viajes) as viajes")
            ->groupBy('tipo_indicadores')->orderBy('tipo')->get();

        return $this->reporteTablaPdf('Resumen Ingresos por Variables',
            [['key'=>'tipo','label'=>'Tipo'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 33 · RESUMEN INGRESOS POR CHOFERES H/FECHA
    public function ingresosResumenChoferes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(ingreso_mt) as ingreso_mt, SUM(salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Ingresos por Chóferes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 60 · CONCILIACION INGRESOS CONTABILIDAD
    public function ingresosConciliacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(ingreso_mt) as ingreso_mt, SUM(flete_demora) as demora")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Conciliación Ingresos Contabilidad',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'demora','label'=>'Demora','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 61 · RESUMEN DEVOLUCIONES CHOFERES
    public function devolucionesChoferes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(descuento) as devolucion")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Devoluciones Chóferes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'devolucion','label'=>'Devolución','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 62 · RESUMEN DEVOLUCIONES CLIENTES
    public function devolucionesClientes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(descuento) as devolucion")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Devoluciones Clientes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'devolucion','label'=>'Devolución','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 64 · RESUMEN DEVOLUCIONES TRACTIVOS
    public function devolucionesTractivos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.descuento) as devolucion")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        return $this->reporteTablaPdf('Resumen Devoluciones Tractivos',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'devolucion','label'=>'Devolución','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 150 · INFORMACION DEL COBRO POR CONCEPTO DE DEMORA
    public function ingresosDemora(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(flete_demora) as demora, SUM(flete_dem_1) as dem_1, SUM(flete_dem_2) as dem_2")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Información del Cobro por Demora',
            [['key'=>'mes','label'=>'Mes'],['key'=>'demora','label'=>'Demora','num'=>true],['key'=>'dem_1','label'=>'Dem 1','num'=>true],['key'=>'dem_2','label'=>'Dem 2','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 4061 · RESUMEN INGRESOS POR CLIENTES SELECCIONADOS
    public function ingresosClientesSeleccionados(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Ingresos por Clientes Seleccionados',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    private function rangoFiltros(array $filtros): array
    {
        $d = $filtros['desde'] ?? null;
        $h = $filtros['hasta'] ?? null;
        if (! $d && ! $h && ! empty($filtros['mes'])) {
            try { $m = Carbon::parse($filtros['mes']); $d = $m->copy()->startOfMonth()->toDateString(); $h = $m->copy()->endOfMonth()->toDateString(); } catch (\Exception) {}
        }
        return [$d, $h];
    }
}

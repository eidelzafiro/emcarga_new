<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\Tractivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — PIZARRA (6 reportes legacy). Versiones funcionales sobre `aforos`
 * y `tractivos` (plan de carga, cumplimiento, estado de situación).
 */
class PizarraReportService extends BaseReportService
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

    // 34 · CUMPLIMIENTO DEL PLAN CARGA
    public function planCarga(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = Aforo::query()->whereNotNull('fecha_parte');
        if ($d) $q->whereBetween('fecha_parte', [$d, $h]);
        $rows = $q->selectRaw("DATE(fecha_parte) as fecha, SUM(tn_real_total) as toneladas, SUM(viajes) as viajes")
            ->groupBy(DB::raw('DATE(fecha_parte)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('Cumplimiento del Plan de Carga',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 35 · DETALLE EQUIPOS OTRAS CAUSAS
    public function pizarraOtras(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()->select('codigo as tractivo', 'estado')->orderBy('codigo')->limit(500)->get();
        return $this->reporteTablaPdf('Detalle Equipos Otras Causas',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 36 · DETALLE EQUIPOS TRANSPORTACION
    public function pizarraTransportacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.tn_real_total) as toneladas, SUM(aforos.km_total_total) as km_total")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        return $this->reporteTablaPdf('Detalle Equipos Transporte',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 37 · INFORMATIVA ESTADO SITUACION
    public function pizarra(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()->selectRaw("estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();
        return $this->reporteTablaPdf('Informativa Estado Situación',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 135 · RESUMEN DE TRABAJO TRACTIVOS EN EL MES
    public function pizarraResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.viajes) as viajes, SUM(aforos.tn_real_total) as toneladas, SUM(aforos.km_total_total) as km_total")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        return $this->reporteTablaPdf('Resumen Trabajo Tractivos en el Mes',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'viajes','label'=>'Viajes','num'=>true],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 370 · CUMPLIMIENTO DEL PLAN CARGA RESUMEN
    public function planCargaResumen(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = Aforo::query()->whereNotNull('fecha_parte');
        if ($d) $q->whereBetween('fecha_parte', [$d, $h]);
        $rows = $q->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(viajes) as viajes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Cumplimiento Plan Carga Resumen',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }
}

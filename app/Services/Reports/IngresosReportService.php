<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\Entidad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — INGRESOS (10 reportes legacy). Versiones funcionales sobre `aforos`
 * (ingreso_mt, descuentos, demora). Chofer/cliente puntual no migrados a
 * Zafiro en esas tablas; se agrupa por la dimensión disponible.
 */
class IngresosReportService extends BaseReportService
{
    protected function mesFiltro(array $filtros): ?string
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

    /**
     * PARTE INGRESOS MENSUALES TRACTIVO — agrupado por tractivo.
     * Columnas: TRACTIVO | CP | TONS (POS/REAL) | KMS (CARGA/VACIOS/TOTAL) | INGRESOS (FLETE/DEMORA/OTROS/IMPORTE)
     */
    public function ingresosPorTractivos(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $entidadId = (int) entidadActivaId();
        $ids = $entidadId ? Entidad::subEntidadesIds($entidadId) : $this->entidadIds();

        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($d, fn ($q) => $q->whereBetween('aforos.fecha_parte', [$d, $h]))
            ->whereIn('tractivos.id_entidad', $ids)
            ->whereNull('tractivos.deleted_at')
            ->whereNull('tractivos.fecha_baja')
            ->selectRaw("
                tractivos.codigo as tractivo,
                COUNT(*) as cp,
                SUM(aforos.tn_pos_total) as tons_pos,
                SUM(aforos.tn_real_total) as tons_real,
                SUM(aforos.km_carga_total) as kms_carga,
                SUM(aforos.km_vacio_total) as kms_vacios,
                SUM(aforos.km_total_total) as kms_total,
                SUM(aforos.flete_mt) as flete,
                SUM(aforos.flete_demora) as demora,
                SUM(aforos.otros_mt) as otros,
                SUM(aforos.ingreso_mt) as importe
            ")
            ->groupBy('tractivos.codigo')
            ->orderBy('tractivos.codigo')
            ->get();

        $columnas = [
            ['key' => 'tractivo', 'label' => 'TRACTIVO'],
            ['key' => 'cp', 'label' => 'CP', 'num' => true],
            ['key' => 'tons_pos', 'label' => 'TONS POS', 'num' => true],
            ['key' => 'tons_real', 'label' => 'TONS REAL', 'num' => true],
            ['key' => 'kms_carga', 'label' => 'KMS CARGA', 'num' => true],
            ['key' => 'kms_vacios', 'label' => 'KMS VACIOS', 'num' => true],
            ['key' => 'kms_total', 'label' => 'KMS TOTAL', 'num' => true],
            ['key' => 'flete', 'label' => 'FLETE', 'num' => true],
            ['key' => 'demora', 'label' => 'DEMORA', 'num' => true],
            ['key' => 'otros', 'label' => 'OTROS', 'num' => true],
            ['key' => 'importe', 'label' => 'IMPORTE', 'num' => true],
        ];

        $filas = $rows->map(fn ($r) => [
            'tractivo' => $r->tractivo,
            'cp' => (int) $r->cp,
            'tons_pos' => round((float) $r->tons_pos, 2),
            'tons_real' => round((float) $r->tons_real, 2),
            'kms_carga' => round((float) $r->kms_carga, 2),
            'kms_vacios' => round((float) $r->kms_vacios, 2),
            'kms_total' => round((float) $r->kms_total, 2),
            'flete' => round((float) $r->flete, 2),
            'demora' => round((float) $r->demora, 2),
            'otros' => round((float) $r->otros, 2),
            'importe' => round((float) $r->importe, 2),
        ])->toArray();

        $totales = [
            'cp' => $rows->sum('cp'),
            'tons_pos' => round($rows->sum('tons_pos'), 2),
            'tons_real' => round($rows->sum('tons_real'), 2),
            'kms_carga' => round($rows->sum('kms_carga'), 2),
            'kms_vacios' => round($rows->sum('kms_vacios'), 2),
            'kms_total' => round($rows->sum('kms_total'), 2),
            'flete' => round($rows->sum('flete'), 2),
            'demora' => round($rows->sum('demora'), 2),
            'otros' => round($rows->sum('otros'), 2),
            'importe' => round($rows->sum('importe'), 2),
        ];

        $periodo = $d ? Carbon::parse($d)->format('M Y') : 'Todos';
        $entidadNombre = $entidadId ? (Entidad::find($entidadId)?->nombre ?? '') : '';

        return $this->reporteTablaPdf('PARTE INGRESOS MENSUALES TRACTIVO',
            $columnas, $filas, [
                'landscape' => true,
                'periodo' => $periodo . ($entidadNombre ? " — {$entidadNombre}" : ''),
                'totales' => $totales,
            ]);
    }

    /**
     * PARTE INGRESOS MENSUALES CHOFERES — agrupado por chofer.
     * Columnas: CHOFER | CP | TONS | HORAS | MN (FLETE/DEMORA) | PRODUCCION AFORADA | % | ESTIMADO (CP/FLETE) | PRODUCCION ESTIMADA
     */
    public function ingresosPorChoferes(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $entidadId = (int) entidadActivaId();
        $ids = $entidadId ? Entidad::subEntidadesIds($entidadId) : $this->entidadIds();

        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->leftJoin('bolsa', 'cartas_porte.id_chofer', '=', 'bolsa.id')
            ->when($d, fn ($q) => $q->whereBetween('aforos.fecha_parte', [$d, $h]))
            ->whereIn('tractivos.id_entidad', $ids)
            ->whereNull('tractivos.deleted_at')
            ->whereNull('tractivos.fecha_baja')
            ->selectRaw("
                CONCAT(COALESCE(bolsa.nombre, ''), ' ', COALESCE(bolsa.apellidos, '')) as chofer,
                COUNT(*) as cp,
                SUM(aforos.tn_real_total) as tons,
                SUM(aforos.tiempo_total) as horas,
                SUM(aforos.flete_mt) as flete,
                SUM(aforos.flete_demora) as demora,
                SUM(aforos.ingreso_mt) as produccion_aforada,
                SUM(aforos.ingreso_mt) as produccion_estimada
            ")
            ->groupBy(DB::raw("CONCAT(COALESCE(bolsa.nombre, ''), ' ', COALESCE(bolsa.apellidos, ''))"))
            ->orderBy(DB::raw("CONCAT(COALESCE(bolsa.nombre, ''), ' ', COALESCE(bolsa.apellidos, ''))"))
            ->get();

        $totalEstimado = $rows->sum('produccion_aforada');

        $columnas = [
            ['key' => 'chofer', 'label' => 'CHOFER'],
            ['key' => 'cp', 'label' => 'CP', 'num' => true],
            ['key' => 'tons', 'label' => 'TONS', 'num' => true],
            ['key' => 'horas', 'label' => 'HORAS', 'num' => true],
            ['key' => 'flete', 'label' => 'FLETE', 'num' => true],
            ['key' => 'demora', 'label' => 'DEMORA', 'num' => true],
            ['key' => 'produccion_aforada', 'label' => 'PRODUCCION AFORADA', 'num' => true],
            ['key' => 'porcentaje', 'label' => '%', 'num' => true],
            ['key' => 'produccion_estimada', 'label' => 'PRODUCCION ESTIMADA', 'num' => true],
        ];

        $filas = $rows->map(fn ($r) => [
            'chofer' => trim($r->chofer) ?: 'SIN CHOFER',
            'cp' => (int) $r->cp,
            'tons' => round((float) $r->tons, 2),
            'horas' => round((float) $r->horas, 2),
            'flete' => round((float) $r->flete, 2),
            'demora' => round((float) $r->demora, 2),
            'produccion_aforada' => round((float) $r->produccion_aforada, 2),
            'porcentaje' => $totalEstimado > 0 ? round(((float) $r->produccion_aforada / $totalEstimado) * 100, 0) : 0,
            'produccion_estimada' => round((float) $r->produccion_estimada, 2),
        ])->toArray();

        $totales = [
            'cp' => $rows->sum('cp'),
            'tons' => round($rows->sum('tons'), 2),
            'horas' => round($rows->sum('horas'), 2),
            'flete' => round($rows->sum('flete'), 2),
            'demora' => round($rows->sum('demora'), 2),
            'produccion_aforada' => round($rows->sum('produccion_aforada'), 2),
            'porcentaje' => 100,
            'produccion_estimada' => round($rows->sum('produccion_estimada'), 2),
        ];

        $periodo = $d ? Carbon::parse($d)->format('M Y') : 'Todos';
        $entidadNombre = $entidadId ? (Entidad::find($entidadId)?->nombre ?? '') : '';

        return $this->reporteTablaPdf('PARTE INGRESOS MENSUALES CHOFERES',
            $columnas, $filas, [
                'landscape' => true,
                'periodo' => $periodo . ($entidadNombre ? " — {$entidadNombre}" : ''),
                'totales' => $totales,
            ]);
    }

    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::subEntidadesIds($activa);
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
}

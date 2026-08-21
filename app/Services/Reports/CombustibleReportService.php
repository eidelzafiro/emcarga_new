<?php

namespace App\Services\Reports;

use App\Models\CombustibleCarga;
use App\Models\CombustibleDescarga;
use App\Models\Tractivo;
use App\Models\Tarjeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase B — COMBUSTIBLE (21 reportes legacy). Versiones funcionales sobre
 * combustible_cargas/descargas, tarjetas y tractivos.
 */
class CombustibleReportService extends BaseReportService
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

    // 39 · RESUMEN CARGAS DEL MES
    public function cargasResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleCarga::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fcarga,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fcarga,'%Y-%m') as mes, SUM(saldocargado) as cargado, SUM(saldoxtarjeta) as xtarjeta, COUNT(*) as partes")
            ->groupBy(DB::raw("DATE_FORMAT(fcarga,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Cargas del Mes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'cargado','label'=>'Cargado','num'=>true],['key'=>'xtarjeta','label'=>'X Tarjeta','num'=>true],['key'=>'partes','label'=>'Partes','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 40 / 42 · SUBMAYOR TARJETAS COMBUSTIBLE
    public function submayor(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->selectRaw("numero as tarjeta, COALESCE(saldo_actual,0) as saldo, estado")
            ->orderBy('numero')->limit(600)->get();
        return $this->reporteTablaPdf('Submayor Tarjetas Combustible',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'saldo','label'=>'Saldo','num'=>true],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 41 · DETALLES DESCARGAS P/TECNICA
    public function descargasTecnica(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->leftJoin('tractivos', 'tarjetas.idtractivos', '=', 'tractivos.id')
            ->when($d, fn ($q) => $q->whereBetween('combustible_descargas.fdescarga', [$d, $h]));
        $rows = $q->selectRaw("combustible_descargas.fdescarga as fecha, tarjetas.numero as tarjeta, COALESCE(tractivos.codigo,'') as tractivo, combustible_descargas.saldo_lts as lts")
            ->orderBy('combustible_descargas.fdescarga')->limit(800)->get();
        return $this->reporteTablaPdf('Detalles Descargas por Técnica',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'tractivo','label'=>'Tractivo'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 43 · TARJETAS CON SALDO
    public function tarjetasConSaldo(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->where('saldo_actual', '>', 0)->select('numero as tarjeta', 'saldo_actual as saldo', 'estado')
            ->orderBy('numero')->limit(600)->get();
        return $this->reporteTablaPdf('Tarjetas Combustible con Saldo',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'saldo','label'=>'Saldo','num'=>true],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 44 / 45 · TARJETAS SIN SALDO
    public function tarjetasSinSaldo(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->where('saldo_actual', '<=', 0)->select('numero as tarjeta', 'saldo_actual as saldo', 'estado')
            ->orderBy('numero')->limit(600)->get();
        return $this->reporteTablaPdf('Tarjetas Combustible sin Saldo',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'saldo','label'=>'Saldo','num'=>true],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 46 · TARJETAS X RESPONSABLE
    public function tarjetasResponsable(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->selectRaw("COALESCE(idempleado,'') as responsable, COUNT(*) as cantidad")
            ->groupBy('idempleado')->orderBy('idempleado')->get();
        return $this->reporteTablaPdf('Tarjetas Combustible por Responsable',
            [['key'=>'responsable','label'=>'Responsable'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 113 · RESUMEN DESCARGAS X VARIABLES
    public function descargasVariables(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fdescarga,'%Y-%m') as mes, SUM(saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Descargas por Variables',
            [['key'=>'mes','label'=>'Mes'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 114 · RESUMEN DESCARGAS POR GRUPO-FECHAS
    public function descargasGrupo(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fdescarga) as fecha, SUM(saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy(DB::raw('DATE(fdescarga)'))->orderBy('fecha')->get();
        return $this->reporteTablaPdf('Resumen Descargas por Grupo-Fechas',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 126 · RESUMEN TARJETAS DEL MES
    public function tarjetasResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tarjeta::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(estado,'') as estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();
        return $this->reporteTablaPdf('Resumen Tarjetas del Mes',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 133 · TARJETAS SIN MOVIMIENTO EN 3 DIAS
    public function tarjetas3Dias(array $filtros): \Illuminate\Http\Response
    {
        $hace3 = Carbon::now()->subDays(3)->toDateString();
        $conMov = CombustibleDescarga::query()->where('fdescarga', '>=', $hace3)->distinct()->pluck('id_tarjeta');
        $rows = Tarjeta::query()->whereNotIn('id', $conMov)->select('numero as tarjeta', 'estado')
            ->orderBy('numero')->limit(600)->get();
        return $this->reporteTablaPdf('Tarjetas sin Movimiento en 3 Días',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 377 · PARTE DIARIO EXISTENCIAS COMBUSTIBLE
    public function parteExistencias(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleCarga::query();
        if ($d) $q->whereBetween('fcarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fcarga) as fecha, SUM(saldocargado) as cargado, COUNT(*) as partes")
            ->groupBy(DB::raw('DATE(fcarga)'))->orderBy('fecha')->get();
        return $this->reporteTablaPdf('Parte Diario Existencias Combustible',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'cargado','label'=>'Cargado','num'=>true],['key'=>'partes','label'=>'Partes','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 1001 · TARJETAS FECHA VENCIMIENTO
    public function tarjetasVencimiento(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->whereNotNull('fvence')->select('numero as tarjeta', 'fvence', 'estado')
            ->orderBy('fvence')->limit(600)->get();
        return $this->reporteTablaPdf('Tarjetas Fecha de Vencimiento',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'fvence','label'=>'Vence'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 1011 · TRACTIVOS CON TANQUE DE COMBUSTIBLE
    public function tractivosTanque(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()->select('codigo as tractivo', 'estado')->orderBy('codigo')->limit(800)->get();
        return $this->reporteTablaPdf('Tractivos con Tanque de Combustible',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 1012 · DESCARGAS SUPERIOR AL TANQUE
    public function descargasSuperior(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query()->orderByDesc('saldo_lts');
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("fdescarga as fecha, saldo_lts as lts")->limit(300)->get();
        return $this->reporteTablaPdf('Descargas Superior al Tanque',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 1013 · DESCARGAS EQUIPOS EN UNA TARJETA
    public function descargasEquiposTarjeta(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(combustible_descargas.fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("tarjetas.numero as tarjeta, SUM(combustible_descargas.saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy('tarjetas.numero')->orderBy('tarjetas.numero')->get();
        return $this->reporteTablaPdf('Descargas Equipos en una Tarjeta',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1020 · ANALISIS COMPORTAMIENTO TARJETAS (ONURE-TODAS)
    public function onureTodas(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tarjeta::query()->selectRaw("numero as tarjeta, COALESCE(saldo_actual,0) as saldo, estado")
            ->orderBy('numero')->limit(600)->get();
        return $this->reporteTablaPdf('Análisis Comportamiento Tarjetas (ONURE-Todas)',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'saldo','label'=>'Saldo','num'=>true],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 1021 · ANALISIS COMPORTAMIENTO TARJETAS (ONURE-DETALLE)
    public function onureDetalle(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query()->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id');
        if ($d) $q->whereBetween('combustible_descargas.fdescarga', [$d, $h]);
        $rows = $q->selectRaw("combustible_descargas.fdescarga as fecha, tarjetas.numero as tarjeta, combustible_descargas.saldo_lts as lts")
            ->orderBy('combustible_descargas.fdescarga')->limit(800)->get();
        return $this->reporteTablaPdf('Análisis Comportamiento Tarjetas (ONURE-Detalle)',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 1023 · ANALISIS COMPORTAMIENTO TARJETAS (VALIDACION)
    public function validacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tarjeta::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(estado,'') as estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();
        return $this->reporteTablaPdf('Análisis Tarjetas (Validación)',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 1031 · ANALISIS COMPORTAMIENTO TARJETAS (VALIDACION-EQUIPOS)
    public function validacionEquipos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(combustible_descargas.fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("tarjetas.numero as tarjeta, SUM(combustible_descargas.saldo_lts) as lts")
            ->groupBy('tarjetas.numero')->orderBy('tarjetas.numero')->get();
        return $this->reporteTablaPdf('Análisis Tarjetas (Validación-Equipos)',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }
}

<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\CombustibleCarga;
use App\Models\CombustibleDescarga;
use App\Models\Tractivo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — ADMINISTRACION (21 reportes legacy). Versiones funcionales sobre las
 * tablas migradas de combustible, tarjetas, cartas de porte, aforos y tractivos.
 */
class AdministracionReportService extends BaseReportService
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

    // 96 · PARTE COMBUSTIBLES
    public function combustibleParte(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleCarga::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fcarga,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fcarga,'%Y-%m') as mes, SUM(saldocargado) as cargado, COUNT(*) as partes")
            ->groupBy(DB::raw("DATE_FORMAT(fcarga,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Parte Combustibles',
            [['key'=>'mes','label'=>'Mes'],['key'=>'cargado','label'=>'Cargado','num'=>true],['key'=>'partes','label'=>'Partes','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 97 · RESUMEN TONELADAS CLIENTES
    public function toneladasClientes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(viajes) as viajes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Toneladas Clientes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 101 / 102 · CARTA PORTE CONTROL ESTADO
    public function cpEstado(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CartaPorte::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_emision,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(estado,'') as estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();

        return $this->reporteTablaPdf('Carta Porte Control Estado',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 103 · RESUMEN DESCARGAS POR GRUPO-FECHAS
    public function combustibleGrupo(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE_FORMAT(fdescarga,'%Y-%m') as mes, SUM(saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Resumen Descargas por Grupo',
            [['key'=>'mes','label'=>'Mes'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 104 · RESUMEN COMBUSTIBLE POR EQUIPOS
    public function combustibleResumen(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(combustible_descargas.fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tarjetas.numero,'') as tarjeta, SUM(combustible_descargas.saldo_lts) as lts")
            ->groupBy('tarjetas.numero')->orderBy('tarjetas.numero')->get();

        return $this->reporteTablaPdf('Resumen Combustible por Equipos',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 105 · CONCILIACION INDICES CONSUMO
    public function indicesConsumo(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fdescarga) as fecha, SUM(saldo_lts) as lts")
            ->groupBy(DB::raw('DATE(fdescarga)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('Conciliación Índices Consumo',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 106 · RESUMEN INGRESOS POR CHOFERES
    public function ingresosChoferes(array $filtros): \Illuminate\Http\Response
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

    // 107 · RESUMEN INGRESOS POR TRACTIVOS
    public function ingresosTractivos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        return $this->reporteTablaPdf('Resumen Ingresos por Tractivos',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 108 / 112 · RESUMEN / DETALLE INDICADORES TRACTIVOS
    public function indicadoresTractivos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.viajes) as viajes, SUM(aforos.tn_real_total) as toneladas, SUM(aforos.km_total_total) as km_total, SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();

        return $this->reporteTablaPdf('Resumen Indicadores Tractivos',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'viajes','label'=>'Viajes','num'=>true],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 109 · CUMPLIMIENTO DEL PLAN CARGA
    public function cumplimientoPlanCarga(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(viajes) as viajes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Cumplimiento del Plan de Carga',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 110 · BALANCE CARGA POR CHOFERES
    public function balanceCargaChoferes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Balance Carga por Chóferes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 111 · PLAN COMBUSTIBLE DEL DIA
    public function planCombustibleDia(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleCarga::query();
        if ($d) $q->whereBetween('fcarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fcarga) as fecha, SUM(saldocargado) as cargado, COUNT(*) as partes")
            ->groupBy(DB::raw('DATE(fcarga)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('Plan Combustible del Día',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'cargado','label'=>'Cargado','num'=>true],['key'=>'partes','label'=>'Partes','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 117 · INFORMATIVA ESTADO SITUACION
    public function estadoSituacion(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()->selectRaw("estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();
        return $this->reporteTablaPdf('Informativa Estado Situación',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 134 · TARJETAS COMBUSTIBLE SIN MOVIMIENTO EN 3 DIAS
    public function tarjetas3Dias(array $filtros): \Illuminate\Http\Response
    {
        $hace3 = Carbon::now()->subDays(3)->toDateString();
        $conMov = CombustibleDescarga::query()->where('fdescarga', '>=', $hace3)->distinct()->pluck('id_tarjeta');
        $rows = \App\Models\Tarjeta::query()->whereNotIn('id', $conMov)->select('numero as tarjeta', 'estado')
            ->orderBy('numero')->limit(500)->get();

        return $this->reporteTablaPdf('Tarjetas sin Movimiento en 3 Días',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 378 · GPS P/CONSEJILLO
    public function gpsConsejilloAdmin(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fdescarga) as fecha, SUM(saldo_lts) as lts")
            ->groupBy(DB::raw('DATE(fdescarga)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('GPS por Consejillo',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $d ? "$d a $h" : 'Todo']);
    }

    // 379 · TARJETAS COMBUSTIBLE X RESPONSABLE
    public function tarjetasResponsable(array $filtros): \Illuminate\Http\Response
    {
        $rows = \App\Models\Tarjeta::query()->selectRaw("COALESCE(idempleado,'') as responsable, COUNT(*) as cantidad")
            ->groupBy('idempleado')->orderBy('idempleado')->get();
        return $this->reporteTablaPdf('Tarjetas por Responsable',
            [['key'=>'responsable','label'=>'Responsable'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 1024 · ANALISIS TARJETAS MAGNETICAS (VALIDACION)
    public function validacionOnure(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = \App\Models\Tarjeta::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(estado,'') as estado, COUNT(*) as cantidad")
            ->groupBy('estado')->orderBy('estado')->get();
        return $this->reporteTablaPdf('Análisis Tarjetas Magnéticas (Validación)',
            [['key'=>'estado','label'=>'Estado'],['key'=>'cantidad','label'=>'Cantidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 1025 · CUMPLIMIENTO PLAN TONELADAS POR CHOFER
    public function planToneladasChofer(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Cumplimiento Plan Toneladas por Chófer',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1032 · ANALISIS TARJETAS (VALIDACION-EQUIPOS)
    public function validacionOnureEquipos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(combustible_descargas.fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tarjetas.numero,'') as tarjeta, SUM(combustible_descargas.saldo_lts) as lts")
            ->groupBy('tarjetas.numero')->orderBy('tarjetas.numero')->get();
        return $this->reporteTablaPdf('Análisis Tarjetas (Validación-Equipos)',
            [['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }
}

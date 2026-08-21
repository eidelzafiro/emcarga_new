<?php

namespace App\Services\Reports;

use App\Models\CombustibleDescarga;
use App\Models\Tractivo;
use App\Models\Aforo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase A — GPS (5 reportes legacy). Versiones funcionales sobre datos migrados
 * (combustible_descargas, tractivos, aforos). GPS puntual no está migrado a
 * Zafiro, así que los reportes de posición son listados de tractivos/consumo.
 */
class GpsReportService extends BaseReportService
{
    private function rangoFechas(array $filtros): array
    {
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        if (! $desde && ! $hasta && ! empty($filtros['mes'])) {
            try {
                $m = Carbon::parse($filtros['mes']);
                $desde = $m->copy()->startOfMonth()->toDateString();
                $hasta = $m->copy()->endOfMonth()->toDateString();
            } catch (\Exception) {}
        }

        return [$desde, $hasta];
    }

    // 21 · COMBUSTIBLE P/CONSEJILLO
    public function gpsConsejillo(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFechas($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE(fdescarga) as fecha, SUM(saldo_lts) as lts")
            ->groupBy(DB::raw('DATE(fdescarga)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('Combustible por Consejillo',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo' => $d ? "$d a $h" : 'Todo']);
    }

    // 22 · CONCILIACION GPS
    public function gpsReporte(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFechas($filtros);
        $q = Aforo::query()->whereNotNull('fecha_parte');
        if ($d) $q->whereBetween('fecha_parte', [$d, $h]);
        $rows = $q->selectRaw("DATE(fecha_parte) as fecha, SUM(km_total_total) as km_total, SUM(ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw('DATE(fecha_parte)'))->orderBy('fecha')->get();

        return $this->reporteTablaPdf('Conciliación GPS',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo' => $d ? "$d a $h" : 'Todo', 'landscape'=>true]);
    }

    // 24 · CONCILIACION INDICES CONSUMO
    public function gpsIndice(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFechas($filtros);
        $q = CombustibleDescarga::query();
        if ($d) $q->whereBetween('fdescarga', [$d, $h]);
        $rows = $q->selectRaw("DATE_FORMAT(fdescarga,'%Y-%m') as mes, SUM(saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Conciliación Índices Consumo',
            [['key'=>'mes','label'=>'Mes'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo' => $d ? "$d a $h" : 'Todo']);
    }

    // 25 · MODELO ANEXO 1 GPS (TODAS)
    public function gpsAnexo1Todas(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()->select('codigo as tractivo', 'marca', 'estado')
            ->orderBy('codigo')->limit(500)->get();

        return $this->reporteTablaPdf('Modelo Anexo 1 GPS (Todas)',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'marca','label'=>'Marca'],['key'=>'estado','label'=>'Estado']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 26 · MODELO ANEXO 1 GPS DETALLE (tractivo)
    public function gpsAnexo1(array $filtros): \Illuminate\Http\Response
    {
        $tractivo = $filtros['tractivo'] ?? null;
        $rows = CombustibleDescarga::query()
            ->join('tarjetas', 'combustible_descargas.id_tarjeta', '=', 'tarjetas.id')
            ->when($tractivo, fn ($q) => $q->where('tarjetas.numero', 'like', "%$tractivo%"))
            ->selectRaw("combustible_descargas.fdescarga as fecha, combustible_descargas.saldo_lts as lts, tarjetas.numero as tarjeta")
            ->orderBy('combustible_descargas.fdescarga')->limit(500)->get();

        return $this->reporteTablaPdf('Modelo Anexo 1 GPS Detalle',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'tarjeta','label'=>'Tarjeta'],['key'=>'lts','label'=>'Litros','num'=>true]],
            $rows->toArray(), ['periodo' => $tractivo ? "Tractivo: $tractivo" : 'Todos', 'landscape'=>true]);
    }
}

<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use Illuminate\Support\Facades\DB;

/**
 * Fase E — EMCARGA (5 reportes legacy, fuente Reportesemcarga):
 *  4062 DISTANCIA MEDIA DE 1 TONELADA (mes), 4063 CARGA TRANSPORTADA,
 *  4064 TRAFICO PRODUCIDO, 4065 KILOMETROS RECORRIDOS TOTALES,
 *  4066 KILOMETROS RECORRIDOS CON CARGA.
 * Versiones funcionales sobre `aforos` migrados (agregados mensuales).
 */
class EmcargaReportService extends BaseReportService
{
    // 4062 · DISTANCIA MEDIA DE 1 TONELADA (mes)
    public function distanciaMediaTonelada(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(km_total_total) as km_total, SUM(tn_real_total) as toneladas, CASE WHEN SUM(tn_real_total) > 0 THEN SUM(km_total_total)/SUM(tn_real_total) ELSE 0 END as distancia_media")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Distancia Media de 1 Tonelada',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'km_total', 'label' => 'Kms Totales', 'num' => true],
                ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
                ['key' => 'distancia_media', 'label' => 'Dist. Media', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 4063 · CARGA TRANSPORTADA
    public function cargaTransportada(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Carga Transportada',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'toneladas', 'label' => 'Toneladas', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 4064 · TRAFICO PRODUCIDO
    public function traficoProducido(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(traf_real_total) as trafico")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Tráfico Producido',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'trafico', 'label' => 'Tráfico', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 4065 · KILOMETROS RECORRIDOS TOTALES
    public function kilometrosTotales(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(km_total_total) as km_totales")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Kilómetros Recorridos Totales',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'km_totales', 'label' => 'Kms Totales', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 4066 · KILOMETROS RECORRIDOS CON CARGA
    public function kilometrosCarga(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(km_carga_total) as km_carga")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Kilómetros Recorridos con Carga',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'km_carga', 'label' => 'Kms con Carga', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }
}

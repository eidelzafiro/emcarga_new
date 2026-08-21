<?php

namespace App\Services\Reports;

use App\Models\Bateria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase D — BATERÍAS (3 reportes legacy, fuente Reportestec):
 *  145 CONTROL DE BATERIAS ACTIVAS X TRACTIVOS
 *  151 PLAN DE BAJAS DE BATERIAS (mes)
 *  152 INFORMACION DE BATERIAS (mes)
 * Versiones funcionales sobre `baterias` migrada (91 filas).
 */
class BateriasReportService extends BaseReportService
{
    // 145 · CONTROL DE BATERIAS ACTIVAS X TRACTIVOS
    public function controlActivasXTractivos(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bateria::query()
            ->whereNull('fecha_retiro')
            ->leftJoin('tractivos', 'baterias.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("tractivos.codigo as tractivo, COALESCE(tractivos.descripcion,'') as descripcion, baterias.folio as folio, baterias.marca as marca, baterias.modelo as modelo, baterias.voltaje as voltaje, baterias.amperaje as amperaje, baterias.fecha_instalacion as fecha_instalacion")
            ->orderBy('tractivos.codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Control de Baterías Activas por Tractivos',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'descripcion', 'label' => 'Descripción'],
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'modelo', 'label' => 'Modelo'],
                ['key' => 'voltaje', 'label' => 'Voltaje', 'num' => true],
                ['key' => 'amperaje', 'label' => 'Amperaje', 'num' => true],
                ['key' => 'fecha_instalacion', 'label' => 'Instalación'],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 151 · PLAN DE BAJAS DE BATERIAS (mes)
    public function planBajas(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Bateria::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_retiro,'%Y-%m')"), $mes))
            ->leftJoin('tractivos', 'baterias.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("baterias.folio as folio, tractivos.codigo as tractivo, baterias.marca as marca, baterias.voltaje as voltaje, baterias.amperaje as amperaje, baterias.fecha_retiro as fecha_retiro")
            ->orderBy('baterias.fecha_retiro')->limit(1500)->get();

        return $this->reporteTablaPdf('Plan de Bajas de Baterías',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'voltaje', 'label' => 'Voltaje', 'num' => true],
                ['key' => 'amperaje', 'label' => 'Amperaje', 'num' => true],
                ['key' => 'fecha_retiro', 'label' => 'Fecha Retiro'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 152 · INFORMACION DE BATERIAS (mes)
    public function informacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Bateria::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_instalacion,'%Y-%m')"), $mes))
            ->leftJoin('tractivos', 'baterias.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("baterias.folio as folio, baterias.marca as marca, baterias.modelo as modelo, baterias.voltaje as voltaje, baterias.amperaje as amperaje, baterias.precio_mn as precio_mn, baterias.precio_me as precio_me, baterias.estado as estado, baterias.fecha_instalacion as fecha_instalacion, tractivos.codigo as tractivo")
            ->orderBy('baterias.folio')->limit(1500)->get();

        return $this->reporteTablaPdf('Información de Baterías',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'modelo', 'label' => 'Modelo'],
                ['key' => 'voltaje', 'label' => 'Voltaje', 'num' => true],
                ['key' => 'amperaje', 'label' => 'Amperaje', 'num' => true],
                ['key' => 'precio_mn', 'label' => 'Precio MN', 'num' => true],
                ['key' => 'precio_me', 'label' => 'Precio ME', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'fecha_instalacion', 'label' => 'Instalación'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }
}

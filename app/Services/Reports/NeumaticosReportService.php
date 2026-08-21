<?php

namespace App\Services\Reports;

use App\Models\Neumatico;
use Illuminate\Support\Facades\DB;

/**
 * Fase D — NEUMÁTICOS (6 reportes legacy, fuente Reportestec).
 *  153 PLAN BAJAS Y RECAUCHES, 154 INFORMACION, 156 CN-2 (neumatico),
 *  162 CN-3 ANALISIS CAUSADO BAJA, 365 LISTADO CAUSADO BAJA,
 *  1000 CONTROL NEUMATICOS ACTIVOS X TRACTIVOS.
 * Versiones funcionales sobre `neumaticos` migrada (562 filas).
 */
class NeumaticosReportService extends BaseReportService
{
    // 153 · PLAN BAJAS Y RECAUCHES (mes)
    public function planBajasRecauches(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Neumatico::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_plan_retiro,'%Y-%m')"), $mes))
            ->leftJoin('tractivos', 'neumaticos.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("neumaticos.folio as folio, tractivos.codigo as tractivo, neumaticos.marca as marca, neumaticos.medida as medida, neumaticos.fecha_plan_aviso as aviso, neumaticos.fecha_plan_retiro as retiro")
            ->orderBy('neumaticos.fecha_plan_retiro')->limit(1500)->get();

        return $this->reporteTablaPdf('Plan de Bajas y Recauches de Neumáticos',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'medida', 'label' => 'Medida'],
                ['key' => 'aviso', 'label' => 'Aviso'],
                ['key' => 'retiro', 'label' => 'Plan Retiro'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 154 · INFORMACION DE NEUMATICOS (mes)
    public function informacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Neumatico::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_instalacion,'%Y-%m')"), $mes))
            ->leftJoin('tractivos', 'neumaticos.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("neumaticos.folio as folio, neumaticos.marca as marca, neumaticos.medida as medida, neumaticos.estado as estado, neumaticos.kilometraje as kilometraje, neumaticos.precio_mn as precio_mn, neumaticos.fecha_instalacion as instalacion, tractivos.codigo as tractivo")
            ->orderBy('neumaticos.folio')->limit(1500)->get();

        return $this->reporteTablaPdf('Información de Neumáticos',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'medida', 'label' => 'Medida'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'kilometraje', 'label' => 'Kms', 'num' => true],
                ['key' => 'precio_mn', 'label' => 'Precio MN', 'num' => true],
                ['key' => 'instalacion', 'label' => 'Instalación'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 156 · CN-2 NEUMATICOS (neumatico específico)
    public function cn2Neumatico(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['neumatico'] ?? null;
        $q = Neumatico::query();
        if (is_numeric($id)) {
            $q->where('id', $id);
        } elseif ($id) {
            $q->where('folio', $id);
        }
        $n = $q->first() ?? new Neumatico(['folio' => $id ?? 'N/A']);
        $rows = [[
            'folio' => $n->folio, 'marca' => $n->marca, 'medida' => $n->medida,
            'estado' => $n->estado, 'kilometraje' => $n->kilometraje,
            'fecha_instalacion' => $n->fecha_instalacion, 'fecha_retiro' => $n->fecha_retiro,
        ]];

        return $this->reporteTablaPdf('CN-2 Neumáticos',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'medida', 'label' => 'Medida'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'kilometraje', 'label' => 'Kms', 'num' => true],
                ['key' => 'fecha_instalacion', 'label' => 'Instalación'],
                ['key' => 'fecha_retiro', 'label' => 'Retiro'],
            ],
            $rows, ['landscape' => true]);
    }

    // 162 · CN-3 ANALISIS NEUMATICOS CAUSADO BAJA (mes)
    public function cn3AnalisisBaja(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Neumatico::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_retiro,'%Y-%m')"), $mes))
            ->whereNotNull('fecha_retiro')
            ->selectRaw("COALESCE(id_posicion,0) as posicion, COUNT(*) as cantidad, SUM(kilometraje) as kilometraje")
            ->groupBy('id_posicion')->orderBy('id_posicion')->get();

        return $this->reporteTablaPdf('CN-3 Análisis de Neumáticos que han Causado Baja',
            [
                ['key' => 'posicion', 'label' => 'Posición', 'num' => true],
                ['key' => 'cantidad', 'label' => 'Cantidad', 'num' => true],
                ['key' => 'kilometraje', 'label' => 'Kms', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 365 · LISTADO NEUMATICOS CAUSADO BAJA (mes)
    public function listadoBaja(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Neumatico::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_retiro,'%Y-%m')"), $mes))
            ->whereNotNull('fecha_retiro')
            ->leftJoin('tractivos', 'neumaticos.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("neumaticos.folio as folio, tractivos.codigo as tractivo, neumaticos.marca as marca, neumaticos.medida as medida, neumaticos.fecha_retiro as retiro, neumaticos.kilometraje as kilometraje")
            ->orderBy('neumaticos.fecha_retiro')->limit(1500)->get();

        return $this->reporteTablaPdf('Listado de Neumáticos que han Causado Baja',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'medida', 'label' => 'Medida'],
                ['key' => 'retiro', 'label' => 'Retiro'],
                ['key' => 'kilometraje', 'label' => 'Kms', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 1000 · CONTROL NEUMATICOS ACTIVOS X TRACTIVOS
    public function activosXTractivos(array $filtros): \Illuminate\Http\Response
    {
        $rows = Neumatico::query()
            ->whereNull('fecha_retiro')
            ->leftJoin('tractivos', 'neumaticos.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("tractivos.codigo as tractivo, neumaticos.folio as folio, neumaticos.marca as marca, neumaticos.medida as medida, neumaticos.estado as estado, neumaticos.kilometraje as kilometraje")
            ->orderBy('tractivos.codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Control de Neumáticos Activos por Tractivos',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'medida', 'label' => 'Medida'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'kilometraje', 'label' => 'Kms', 'num' => true],
            ],
            $rows->toArray(), ['landscape' => true]);
    }
}

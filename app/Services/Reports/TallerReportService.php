<?php

namespace App\Services\Reports;

use App\Models\Motore;
use App\Models\OrdenesTaller;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

/**
 * Fase D — CONTROL TALLER (12 reportes legacy, fuente Reportestec).
 * Versiones funcionales sobre ordenes_taller / tractivos / motores / baterias /
 * control_lubricantes (datos migrados). El detalle de una OT individual ya
 * existe en OrdenTallerReportService::pdfOrdenTaller.
 */
class TallerReportService extends BaseReportService
{
    // 137 · CT-2 CONTROL MTTO Y REPARACIONES EVENTUALES (tractivo)
    public function ct2MttoEventuales(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['tractivo'] ?? null;
        $rows = OrdenesTaller::query()
            ->when($id && is_numeric($id), fn ($q) => $q->where('id_tractivo', $id))
            ->when($id && ! is_numeric($id), fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->where('codigo', $id)))
            ->with('tractivo:id,codigo,descripcion')
            ->selectRaw("id, numero, COALESCE(fecha_ingreso,'') as fecha_ingreso, COALESCE(fecha_salida,'') as fecha_salida, COALESCE(estado,'') as estado")
            ->orderByDesc('id')->limit(800)->get()
            ->map(fn ($o) => [
                'numero' => $o->numero,
                'tractivo' => $o->tractivo?->codigo ?? '',
                'fecha_ingreso' => $o->fecha_ingreso,
                'fecha_salida' => $o->fecha_salida,
                'estado' => $o->estado,
            ])->toArray();

        return $this->reporteTablaPdf('CT-2 Control de Mtto. y Reparaciones Eventuales',
            [
                ['key' => 'numero', 'label' => 'OT'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'fecha_ingreso', 'label' => 'Entrada'],
                ['key' => 'fecha_salida', 'label' => 'Salida'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows, ['landscape' => true]);
    }

    // 142 · CT-5 MOVIMIENTO VEHICULOS EN TALLER (mes)
    public function ct5MovimientoMes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = OrdenesTaller::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_ingreso,'%Y-%m') as mes, COUNT(*) as ordenes, SUM(CASE WHEN estado='abierta' THEN 1 ELSE 0 END) as abiertas")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('CT-5 Movimiento de Vehículos en Taller',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'ordenes', 'label' => 'Órdenes', 'num' => true],
                ['key' => 'abiertas', 'label' => 'Abiertas', 'num' => true],
            ],
            $rows->map(fn ($r) => (array) $r)->toArray(),
            ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 146 · CT-8 CONTROL VIDA UTIL BATERIA
    public function ct8VidaUtilBateria(array $filtros): \Illuminate\Http\Response
    {
        $rows = \App\Models\Bateria::query()
            ->leftJoin('tractivos', 'baterias.id_tractivo', '=', 'tractivos.id')
            ->selectRaw("baterias.folio as folio, tractivos.codigo as tractivo, baterias.fecha_instalacion as instalacion, baterias.fecha_retiro as retiro, baterias.voltaje as voltaje, baterias.amperaje as amperaje")
            ->orderBy('baterias.folio')->limit(1500)->get();

        return $this->reporteTablaPdf('CT-8 Control de la Vida Útil de la Batería',
            [
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'instalacion', 'label' => 'Instalación'],
                ['key' => 'retiro', 'label' => 'Retiro'],
                ['key' => 'voltaje', 'label' => 'Voltaje', 'num' => true],
                ['key' => 'amperaje', 'label' => 'Amperaje', 'num' => true],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 147 · CT-3 TIEMPO TRABAJO AGREGADOS INTERCAMBIADOS (mes)
    public function ct3TiempoAgregados(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = OrdenesTaller::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_ingreso,'%Y-%m') as mes, SUM(COALESCE(ottiempo,0)) as horas, COUNT(*) as ordenes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('CT-3 Tiempo de Trabajo de los Agregados Intercambiados',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'horas', 'label' => 'Horas', 'num' => true],
                ['key' => 'ordenes', 'label' => 'Órdenes', 'num' => true],
            ],
            $rows->map(fn ($r) => (array) $r)->toArray(),
            ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 148 · CT-1 EXPEDIENTE TECNICO VEHICULOS (tractivo2)
    public function ct1Expediente(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['tractivo'] ?? ($filtros['tractivo2'] ?? null);
        $q = Tractivo::query();
        if (is_numeric($id)) {
            $q->where('id', $id);
        } elseif ($id) {
            $q->where('codigo', $id);
        }
        $t = $q->first() ?? new Tractivo(['codigo' => $id ?? 'N/A']);
        $rows = [[
            'codigo' => $t->codigo, 'descripcion' => $t->descripcion, 'estado' => $t->estado,
            'marca' => $t->marca, 'modelo' => $t->modelo, 'anno' => $t->anno,
            'kilometraje_actual' => $t->kilometraje_actual, 'capacidad_toneladas' => $t->capacidad_toneladas,
        ]];

        return $this->reporteTablaPdf('CT-1 Expediente Técnico Vehículos',
            [
                ['key' => 'codigo', 'label' => 'Tractivo'],
                ['key' => 'descripcion', 'label' => 'Descripción'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'modelo', 'label' => 'Modelo'],
                ['key' => 'anno', 'label' => 'Año', 'num' => true],
                ['key' => 'kilometraje_actual', 'label' => 'Kms', 'num' => true],
                ['key' => 'capacidad_toneladas', 'label' => 'Cap. Ton.', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows, ['landscape' => true]);
    }

    // 149 · CT-7 CONTROL LUBRICANTES (mes)
    public function ct7ControlLubricantes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = \App\Models\ControlLubricante::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_cambio,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_cambio,'%Y-%m') as mes, SUM(litros_motor) as motor, SUM(litros_transmision) as transmision, SUM(litros_hidraulico) as hidraulico, SUM(grasa_rollete) as grasa_rollete")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_cambio,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('CT-7 Control de Lubricantes',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'motor', 'label' => 'Motor', 'num' => true],
                ['key' => 'transmision', 'label' => 'Transmisión', 'num' => true],
                ['key' => 'hidraulico', 'label' => 'Hidráulico', 'num' => true],
                ['key' => 'grasa_rollete', 'label' => 'Grasa Rollete', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 159 · CT-6 ANALISIS VIDA MOTORES (motores)
    public function ct6AnalisisMotores(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['motores'] ?? null;
        $rows = Motore::query()
            ->when($id && is_numeric($id), fn ($q) => $q->where('id', $id))
            ->selectRaw("codigo as codigo, COALESCE(marca,'') as marca, COALESCE(modelo,'') as modelo, COALESCE(numero_serie,'') as numero_serie, COALESCE(estado,'') as estado")
            ->orderBy('codigo')->limit(800)->get();

        return $this->reporteTablaPdf('CT-6 Análisis de la Vida de los Motores',
            [
                ['key' => 'codigo', 'label' => 'Código'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'modelo', 'label' => 'Modelo'],
                ['key' => 'numero_serie', 'label' => 'Nro. Serie'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 160 · CT-4 REPORTE REPARACION Y MANTENIMIENTO (ordenes)
    public function ct4ReparacionMantenimiento(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = OrdenesTaller::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"), $mes))
            ->with('tractivo:id,codigo')
            ->selectRaw("id, numero, COALESCE(fecha_ingreso,'') as fecha_ingreso, COALESCE(estado,'') as estado, COALESCE(id_motivo_entrada,'') as motivo")
            ->orderByDesc('id')->limit(800)->get()
            ->map(fn ($o) => [
                'numero' => $o->numero, 'tractivo' => $o->tractivo?->codigo ?? '',
                'fecha_ingreso' => $o->fecha_ingreso, 'motivo' => $o->motivo, 'estado' => $o->estado,
            ])->toArray();

        return $this->reporteTablaPdf('CT-4 Reporte de Reparación y Mantenimiento',
            [
                ['key' => 'numero', 'label' => 'OT'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'fecha_ingreso', 'label' => 'Entrada'],
                ['key' => 'motivo', 'label' => 'Motivo'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows, ['landscape' => true]);
    }

    // 163 · CT-5 MOVIMIENTO TALLER (FECHA)
    public function ct5MovimientoFecha(array $filtros): \Illuminate\Http\Response
    {
        [$d, $h] = $this->rangoFiltros($filtros);
        $rows = OrdenesTaller::query()
            ->when($d, fn ($q) => $q->whereDate('fecha_ingreso', '>=', $d))
            ->when($h, fn ($q) => $q->whereDate('fecha_ingreso', '<=', $h))
            ->with('tractivo:id,codigo')
            ->selectRaw("id, numero, COALESCE(fecha_ingreso,'') as fecha_ingreso, COALESCE(fecha_salida,'') as fecha_salida, COALESCE(estado,'') as estado")
            ->orderBy('fecha_ingreso')->limit(800)->get()
            ->map(fn ($o) => [
                'numero' => $o->numero, 'tractivo' => $o->tractivo?->codigo ?? '',
                'fecha_ingreso' => $o->fecha_ingreso, 'fecha_salida' => $o->fecha_salida, 'estado' => $o->estado,
            ])->toArray();

        return $this->reporteTablaPdf('CT-5 Movimiento de Vehículos en Taller (Fecha)',
            [
                ['key' => 'numero', 'label' => 'OT'],
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'fecha_ingreso', 'label' => 'Entrada'],
                ['key' => 'fecha_salida', 'label' => 'Salida'],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows, ['landscape' => true]);
    }

    // 343 · DATOS GENERALES PARQUE AUTOMOTOR
    public function datosGeneralesParque(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()
            ->selectRaw("codigo as codigo, COALESCE(descripcion,'') as descripcion, COALESCE(marca,'') as marca, COALESCE(modelo,'') as modelo, COALESCE(anno,'') as anno, COALESCE(estado,'') as estado, COALESCE(kilometraje_actual,0) as kilometraje_actual")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Datos Generales del Parque Automotor',
            [
                ['key' => 'codigo', 'label' => 'Tractivo'],
                ['key' => 'descripcion', 'label' => 'Descripción'],
                ['key' => 'marca', 'label' => 'Marca'],
                ['key' => 'modelo', 'label' => 'Modelo'],
                ['key' => 'anno', 'label' => 'Año', 'num' => true],
                ['key' => 'kilometraje_actual', 'label' => 'Kms', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 344 · CRT - CIRCULACION - LICENCIA OPERATIVA
    public function crtCirculacion(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()
            ->selectRaw("codigo as codigo, COALESCE(circulacion,'') as circulacion, COALESCE(femision_circ,'') as femision_circ, COALESCE(fvence_circ,'') as fvence_circ, COALESCE(ficav,'') as ficav, COALESCE(fvence_ficav,'') as fvence_ficav, COALESCE(lot,'') as lot, COALESCE(fvence_lot,'') as fvence_lot")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('CRT - Circulación - Licencia Operativa',
            [
                ['key' => 'codigo', 'label' => 'Tractivo'],
                ['key' => 'circulacion', 'label' => 'Circulación'],
                ['key' => 'femision_circ', 'label' => 'Emisión Circ.'],
                ['key' => 'fvence_circ', 'label' => 'Vence Circ.'],
                ['key' => 'ficav', 'label' => 'FICAV'],
                ['key' => 'fvence_ficav', 'label' => 'Vence FICAV'],
                ['key' => 'lot', 'label' => 'LOT'],
                ['key' => 'fvence_lot', 'label' => 'Vence LOT'],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 361 · MANTENIMIENTOS REALIZADOS EN OTRAS ENTIDADES (mes)
    public function mttosExterior(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = OrdenesTaller::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"), $mes))
            ->whereNotNull('id_taller')
            ->selectRaw("DATE_FORMAT(fecha_ingreso,'%Y-%m') as mes, COUNT(*) as ordenes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_ingreso,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Mantenimientos Realizados en Otras Entidades',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'ordenes', 'label' => 'Órdenes', 'num' => true],
            ],
            $rows->map(fn ($r) => (array) $r)->toArray(),
            ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }
}

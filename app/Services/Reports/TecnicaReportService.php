<?php

namespace App\Services\Reports;

use App\Models\ControlLubricante;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

/**
 * Fase D — TÉCNICA (10 reportes legacy, fuente Reportestec):
 *  138 INFORME ESTADO TÉCNICO PARQUE, 139 INFORME ANUAL POR VEHÍCULO,
 *  140 SITUACIÓN TÉCNICA PARQUE, 144 CONTROL CDT, 155 DISPONIBILIDAD (KMS),
 *  161 VEHÍCULOS ÍNDICES CONSUMO DETERIORADOS, 366 DISPONIBILIDAD VAYAS,
 *  367 OPERACIONES TALLER X OPERARIOS, 369 GASTO LUBRICANTES/GRASAS/LÍQUIDOS,
 *  376 CONCILIACIÓN DISPONIBILIDAD VEHÍCULOS.
 * Versiones funcionales sobre tractivos / control_lubricantes / ordenes_taller.
 */
class TecnicaReportService extends BaseReportService
{
    // 138 · INFORME DEL ESTADO TÉCNICO DEL PARQUE (mes)
    public function informeEstadoParque(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tractivo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(estado,'') as estado, COUNT(*) as cantidad, SUM(kilometraje_actual) as kms")
            ->groupBy('estado')->orderBy('estado')->get();

        return $this->reporteTablaPdf('Informe del Estado Técnico del Parque',
            [
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'cantidad', 'label' => 'Cantidad', 'num' => true],
                ['key' => 'kms', 'label' => 'Kms', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 139 · INFORME ANUAL ESTADO TÉCNICO POR VEHÍCULO (tractivo)
    public function informeAnualTractivo(array $filtros): \Illuminate\Http\Response
    {
        $id = $filtros['tractivo'] ?? null;
        $q = Tractivo::query();
        if (is_numeric($id)) {
            $q->where('id', $id);
        } elseif ($id) {
            $q->where('codigo', $id);
        }
        $t = $q->first();
        if (! $t) {
            $t = new Tractivo(['codigo' => $id ?? 'N/A']);
        }
        $rows = [[
            'codigo' => $t->codigo,
            'descripcion' => $t->descripcion,
            'estado' => $t->estado,
            'kilometraje_actual' => $t->kilometraje_actual,
            'kms_disp' => $t->kms_disp,
            'kms_plan_mtto' => $t->kms_plan_mtto,
            'indice_consumo' => $t->indice_consumo,
            'indice_aceite' => $t->indice_aceite,
            'plan_cdt' => $t->plan_cdt,
        ]];

        return $this->reporteTablaPdf('Informe Anual del Estado Técnico por Vehículo',
            [
                ['key' => 'codigo', 'label' => 'Tractivo'],
                ['key' => 'descripcion', 'label' => 'Descripción'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'kilometraje_actual', 'label' => 'Kms Actual', 'num' => true],
                ['key' => 'kms_disp', 'label' => 'Kms Disp.', 'num' => true],
                ['key' => 'kms_plan_mtto', 'label' => 'Plan Mtto', 'num' => true],
                ['key' => 'indice_consumo', 'label' => 'Índice Comb.', 'num' => true],
                ['key' => 'indice_aceite', 'label' => 'Índice Aceite', 'num' => true],
                ['key' => 'plan_cdt', 'label' => 'Plan CDT', 'num' => true],
            ],
            $rows, ['landscape' => true]);
    }

    // 140 · SITUACIÓN TÉCNICA PARQUE (por grupo/estado)
    public function situacionParque(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()
            ->selectRaw("COALESCE(id_grupo,0) as grupo, COALESCE(estado,'') as estado, COUNT(*) as cantidad")
            ->groupBy('id_grupo', 'estado')->orderBy('id_grupo')->orderBy('estado')->get();

        return $this->reporteTablaPdf('Situación Técnica Parque de Vehículos',
            [
                ['key' => 'grupo', 'label' => 'Grupo', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'cantidad', 'label' => 'Cantidad', 'num' => true],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 144 · CONTROL CDT (disponibilidad técnica por tractivo)
    public function controlCdt(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tractivo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("codigo as tractivo, COALESCE(kms_disp,0) as kms_disp, COALESCE(plan_cdt,0) as plan_cdt, COALESCE(estado,'') as estado")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Control del Coeficiente de Disponibilidad Técnica (CDT)',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'kms_disp', 'label' => 'Kms Disp.', 'num' => true],
                ['key' => 'plan_cdt', 'label' => 'Plan CDT', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 155 · DISPONIBILIDAD (KMS) DE VEHÍCULOS
    public function disponibilidadKms(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()
            ->selectRaw("codigo as tractivo, COALESCE(kilometraje_actual,0) as kilometraje_actual, COALESCE(kms_disp,0) as kms_disp, COALESCE(kms_plan_mtto,0) as kms_plan_mtto")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Disponibilidad (Kms) de Vehículos',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'kilometraje_actual', 'label' => 'Kms Actual', 'num' => true],
                ['key' => 'kms_disp', 'label' => 'Kms Disp.', 'num' => true],
                ['key' => 'kms_plan_mtto', 'label' => 'Plan Mtto', 'num' => true],
            ],
            $rows->toArray(), ['landscape' => true]);
    }

    // 161 · VEHÍCULOS CON ÍNDICES DE CONSUMO DETERIORADOS (mes)
    public function indicesDeteriorados(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tractivo::query()
            ->whereNotNull('indice_consumo')
            ->where('indice_consumo', '>', 0)
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->orderByDesc('indice_consumo')->limit(1500)
            ->selectRaw("codigo as tractivo, COALESCE(indice_consumo,0) as indice_consumo, COALESCE(indice_aceite,0) as indice_aceite, COALESCE(estado,'') as estado")
            ->get();

        return $this->reporteTablaPdf('Vehículos con Índices de Consumos Deteriorados',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'indice_consumo', 'label' => 'Índice Comb.', 'num' => true],
                ['key' => 'indice_aceite', 'label' => 'Índice Aceite', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 366 · DISPONIBILIDAD DE VAYAS (mes) — paridad funcional sobre disponibilidad
    public function disponibilidadVayas(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Tractivo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("codigo as tractivo, COALESCE(kms_disp,0) as kms_disp, COALESCE(plan_cdt,0) as plan_cdt, COALESCE(estado,'') as estado")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Disponibilidad de Vayas',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'kms_disp', 'label' => 'Kms Disp.', 'num' => true],
                ['key' => 'plan_cdt', 'label' => 'Plan CDT', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 367 · OPERACIONES REALIZADAS EN TALLER X OPERARIOS (mes)
    public function operacionesTallerOperarios(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = DB::table('ordenes_taller')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as mes, COUNT(*) as ordenes")
            ->groupBy(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Operaciones Realizadas en Taller por Operarios',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'ordenes', 'label' => 'Órdenes', 'num' => true],
            ],
            $rows->map(fn ($r) => (array) $r)->toArray(),
            ['periodo' => $mes ? "Mes: $mes" : 'Todos']);
    }

    // 369 · GASTO DE LUBRICANTES, GRASAS Y LÍQUIDOS (mes)
    public function gastoLubricantes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = ControlLubricante::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_cambio,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_cambio,'%Y-%m') as mes, SUM(litros_motor) as motor, SUM(litros_transmision) as transmision, SUM(litros_direccion) as direccion, SUM(litros_hidraulico) as hidraulico, SUM(grasa_rollete) as grasa_rollete, SUM(grasa_copillas) as grasa_copillas, SUM(agua_refrigerada) as agua")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_cambio,'%Y-%m')"))->orderBy('mes')->get();

        return $this->reporteTablaPdf('Gasto de Lubricantes, Grasas y Líquidos',
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'motor', 'label' => 'Motor', 'num' => true],
                ['key' => 'transmision', 'label' => 'Transmisión', 'num' => true],
                ['key' => 'direccion', 'label' => 'Dirección', 'num' => true],
                ['key' => 'hidraulico', 'label' => 'Hidráulico', 'num' => true],
                ['key' => 'grasa_rollete', 'label' => 'Grasa Rollete', 'num' => true],
                ['key' => 'grasa_copillas', 'label' => 'Grasa Copillas', 'num' => true],
                ['key' => 'agua', 'label' => 'Agua', 'num' => true],
            ],
            $rows->toArray(), ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 376 · CONCILIACIÓN DISPONIBILIDAD VEHÍCULOS
    public function conciliacionDisponibilidad(array $filtros): \Illuminate\Http\Response
    {
        $rows = Tractivo::query()
            ->selectRaw("codigo as tractivo, COALESCE(kilometraje_actual,0) as kilometraje_actual, COALESCE(kms_disp,0) as kms_disp, COALESCE(kms_plan_mtto,0) as kms_plan_mtto, COALESCE(estado,'') as estado")
            ->orderBy('codigo')->limit(1500)->get();

        return $this->reporteTablaPdf('Conciliación Disponibilidad Vehículos',
            [
                ['key' => 'tractivo', 'label' => 'Tractivo'],
                ['key' => 'kilometraje_actual', 'label' => 'Kms Actual', 'num' => true],
                ['key' => 'kms_disp', 'label' => 'Kms Disp.', 'num' => true],
                ['key' => 'kms_plan_mtto', 'label' => 'Plan Mtto', 'num' => true],
                ['key' => 'estado', 'label' => 'Estado'],
            ],
            $rows->toArray(), ['landscape' => true]);
    }
}

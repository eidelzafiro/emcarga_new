<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reporte de costo por tractivo expuesto a la API móvil.
 */
class ReporteCostoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_reporte' => optional($this->fecha_reporte)->toDateString(),
            'id_tractivo' => $this->id_tractivo,
            'tractivo' => $this->whenLoaded('tractivo', fn () => $this->tractivo?->codigo),
            'combustible_mn' => $this->combustible_mn,
            'lubricante_mn' => $this->lubricante_mn,
            'piezas_mn' => $this->piezas_mn,
            'salario_total' => $this->salario_total,
            'dietas' => $this->dietas,
            'gastos_mn' => $this->gastos_mn,
            'ingresos_mn' => $this->ingresos_mn,
            'kms_total' => $this->kms_total,
            'toneladas' => $this->toneladas,
            'utilidad_mn' => $this->utilidad_mn,
            'costo_mn' => $this->costo_mn,
            'costo_tn_kms' => $this->costo_tn_kms,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orden de taller expuesta a la API móvil.
 */
class OrdenTallerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'fecha_ingreso' => optional($this->fecha_ingreso)->toDateString(),
            'fecha_salida' => optional($this->fecha_salida)->toDateString(),
            'estado' => $this->estado,
            'diagnostico' => $this->diagnostico,
            'tipo_mtto' => $this->tipo_mtto,
            'km_mtto' => $this->km_mtto,
            'id_entidad' => $this->id_entidad,
            'tractivo' => $this->whenLoaded('tractivo', fn () => $this->tractivo?->codigo),
            'tipo_mantenimiento' => $this->whenLoaded('tipoMantenimiento', fn () => $this->tipoMantenimiento?->nombre),
            'motivo_entrada' => $this->whenLoaded('motivoEntrada', fn () => $this->motivoEntrada?->nombre),
            'clasificacion' => $this->whenLoaded('clasificacion', fn () => $this->clasificacion?->nombre),
            'operaciones' => $this->whenLoaded('operaciones', fn () => $this->operaciones->map(fn ($o) => [
                'id' => $o->id,
                'operario' => $o->operario?->nombre ?? null,
                'horas' => $o->tiempo ?? null,
                'fecha' => optional($o->fecha_inicio)->toDateString(),
            ])->values()),
            'gastos' => $this->whenLoaded('gastos', fn () => $this->gastos->map(fn ($g) => [
                'id' => $g->id,
                'codigo_pieza' => $g->codigo_pieza,
                'cantidad' => $g->cantidad,
                'motivo' => $g->motivo,
            ])->values()),
        ];
    }
}

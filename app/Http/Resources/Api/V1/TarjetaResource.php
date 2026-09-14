<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tarjeta de combustible expuesta a la API móvil.
 */
class TarjetaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'saldo_actual' => $this->saldo_actual,
            'saldoactuallts' => $this->saldoactuallts,
            'limite_credito' => $this->limite_credito,
            'fcompra' => optional($this->fcompra)->toDateString(),
            'fvence' => optional($this->fvence)->toDateString(),
            'estado' => $this->estado,
            'id_entidad' => $this->id_entidad,
            'moneda' => $this->whenLoaded('moneda', fn () => $this->moneda?->codigo),
            'tipo_combustible' => $this->whenLoaded('tipoCombustible', fn () => $this->tipoCombustible?->nombre),
            'empleado' => $this->whenLoaded('empleado', fn () => trim(($this->empleado?->nombre ?? '').' '.($this->empleado?->apellidos ?? ''))),
            'tractivo' => $this->whenLoaded('tractivo', fn () => $this->tractivo?->codigo),
        ];
    }
}

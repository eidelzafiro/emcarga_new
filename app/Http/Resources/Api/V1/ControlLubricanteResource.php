<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Registro CT-7 de control de lubricantes expuesto a la API móvil.
 */
class ControlLubricanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_cambio' => optional($this->fecha_cambio)->toDateString(),
            'tipo_operacion' => $this->tipo_operacion,
            'id_tractivo' => $this->id_tractivo,
            'tractivo' => $this->whenLoaded('tractivo', fn () => $this->tractivo?->descripcion ?? $this->tractivo?->placa),
            'id_entidad' => $this->id_entidad,
            'entidad' => $this->whenLoaded('entidad', fn () => $this->entidad?->nombre),
            'litros' => [
                'motor' => $this->litros_motor,
                'transmision' => $this->litros_transmision,
                'direccion' => $this->litros_direccion,
                'hidraulico' => $this->litros_hidraulico,
                'freno' => $this->liquido_freno,
                'agua' => $this->agua_refrigerada,
                'grasa_rollete' => $this->grasa_rollete,
                'grasa_copillas' => $this->grasa_copillas,
            ],
        ];
    }
}

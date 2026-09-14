<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Acuerdo de precio expuesto a la API móvil.
 */
class AcuerdoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_entidad' => $this->id_entidad,
            'id_cliente' => $this->id_cliente,
            'cliente' => $this->whenLoaded('cliente', fn () => $this->cliente?->nombre),
            'origen' => $this->whenLoaded('origen', fn () => $this->origen?->nombre),
            'destino' => $this->whenLoaded('destino', fn () => $this->destino?->nombre),
            'producto' => $this->whenLoaded('producto', fn () => $this->producto?->nombre),
            'tarifa_ton' => $this->tarifa_ton,
            'importe' => $this->importe,
            'activo' => (bool) $this->activo,
        ];
    }
}

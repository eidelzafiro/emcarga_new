<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tractivo expuesto a la API móvil (sin datos sensibles).
 */
class TractivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'placa' => $this->placa,
            'anno' => $this->anno,
            'tara' => $this->tara,
            'capacidad_toneladas' => $this->capacidad_toneladas,
            'kilometraje_actual' => $this->kilometraje_actual,
            'id_entidad' => $this->id_entidad,
            'tipo_equipo' => $this->whenLoaded('tipoVehiculo', fn () => $this->tipoVehiculo?->tipoEquipo?->nombre),
            'marca' => $this->whenLoaded('tipoVehiculo', fn () => $this->tipoVehiculo?->marca?->nombre),
            'modelo' => $this->whenLoaded('tipoVehiculo', fn () => $this->tipoVehiculo?->modelo?->nombre),
            'motor' => $this->whenLoaded('motor', fn () => $this->motor?->codigo),
            'caja' => $this->whenLoaded('caja', fn () => $this->caja?->codigo),
            'diferencial' => $this->whenLoaded('diferencial', fn () => $this->diferencial?->codigo),
        ];
    }
}

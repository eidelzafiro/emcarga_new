<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cliente expuesto a la API móvil.
 */
class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'nrocontrato' => $this->nrocontrato,
            'nit' => $this->nit,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'id_entidad' => $this->id_entidad,
            'organismo' => $this->whenLoaded('organismo', fn () => $this->organismo?->nombre),
            'moneda' => $this->whenLoaded('moneda', fn () => $this->moneda?->codigo),
        ];
    }
}

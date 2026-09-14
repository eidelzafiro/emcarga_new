<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lugar expuesto a la API móvil.
 */
class LugareResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'provincia' => $this->provincia,
            'municipio' => $this->municipio,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'activo' => (bool) $this->activo,
        ];
    }
}

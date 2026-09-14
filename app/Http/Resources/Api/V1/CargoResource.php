<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Cargo expuesto a la API móvil.
 */
class CargoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'es_chofer' => (bool) $this->es_chofer,
            'activo' => (bool) $this->activo,
            'id_entidad' => $this->id_entidad,
            'salario_escala' => $this->salario_escala,
            'tarifa' => $this->tarifa,
        ];
    }
}

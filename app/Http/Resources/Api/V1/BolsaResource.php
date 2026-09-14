<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bolsa (empleado) expuesta a la API móvil.
 */
class BolsaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ci' => $this->ci,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'nombre_completo' => trim(($this->nombre ?? '').' '.($this->apellidos ?? '')),
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'falta' => optional($this->falta)->toDateString(),
            'id_entidad' => $this->id_entidad,
            'id_cargo' => $this->id_cargo,
            'id_area' => $this->id_area,
            'cargo' => $this->whenLoaded('cargo', fn () => $this->cargo?->nombre),
            'area' => $this->whenLoaded('area', fn () => $this->area?->nombre),
            'entidad' => $this->whenLoaded('entidad', fn () => $this->entidad?->abreviatura ?? $this->entidad?->nombre),
        ];
    }
}

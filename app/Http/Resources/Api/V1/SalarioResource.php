<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Salario expuesto a la API móvil (mes de nómina del empleado).
 */
class SalarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mes' => (int) $this->mes,
            'ano' => (int) $this->ano,
            'numero_nomina' => $this->numero_nomina,
            'id_bolsa' => $this->id_bolsa,
            'id_entidad' => $this->id_entidad,
            'nombre_completo' => $this->whenLoaded('bolsa', fn () => trim(($this->bolsa?->nombre ?? '').' '.($this->bolsa?->apellidos ?? ''))),
            'ci' => $this->whenLoaded('bolsa', fn () => $this->bolsa?->ci),
            'cargo' => $this->whenLoaded('cargo', fn () => $this->cargo?->nombre),
            'area' => $this->whenLoaded('area', fn () => $this->area?->nombre),
            'salario_base' => $this->salario_base,
            'plus' => $this->plus,
            't_total' => $this->t_total,
            'imp_salario_final' => $this->imp_salario_final,
            'estado' => $this->estado,
        ];
    }
}

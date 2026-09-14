<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Factura expuesta a la API móvil.
 */
class FacturaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'fecha_emision' => optional($this->fecha_emision)->toDateString(),
            'id_cliente' => $this->id_cliente,
            'cliente' => $this->whenLoaded('cliente', fn () => $this->cliente?->nombre),
            'tipo_ingreso' => $this->whenLoaded('tipoIngreso', fn () => $this->tipoIngreso?->nombre),
            'flete_mt' => $this->flete_mt,
            'flete_mlc' => $this->flete_mlc,
            'flete_demora' => $this->flete_demora,
            'otros_mt' => $this->otros_mt,
            'ingreso_mt' => $this->ingreso_mt,
            'estado' => $this->estado,
            'cancelada' => (bool) $this->cancelada,
            'id_entidad' => $this->id_entidad,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Descarga de combustible expuesta a la API móvil.
 */
class CombustibleDescargaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fdescarga' => optional($this->fdescarga)->toDateString(),
            'hora_descarga' => $this->hora_descarga,
            'folio' => $this->folio,
            'saldo_mon' => $this->saldo_mon,
            'saldo_lts' => $this->saldo_lts,
            'kms' => $this->kms,
            'id_entidad' => $this->id_entidad,
            'tarjeta' => $this->whenLoaded('tarjeta', fn () => $this->tarjeta?->numero),
            'hoja_ruta' => $this->whenLoaded('hojaRuta', fn () => $this->hojaRuta?->numero),
            'tractivo' => $this->whenLoaded('tractivo', fn () => $this->tractivo?->codigo),
            'empleado' => $this->whenLoaded('empleado', fn () => trim(($this->empleado?->nombre ?? '').' '.($this->empleado?->apellidos ?? ''))),
            'servicentro' => $this->whenLoaded('servicentro', fn () => $this->servicentro?->nombre),
        ];
    }
}

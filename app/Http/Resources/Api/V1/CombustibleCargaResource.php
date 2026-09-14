<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Carga de combustible expuesta a la API móvil.
 */
class CombustibleCargaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fcarga' => optional($this->fcarga)->toDateString(),
            'folio' => $this->folio,
            'saldocargado' => $this->saldocargado,
            'saldoxtarjeta' => $this->saldoxtarjeta,
            'notas' => $this->notas,
            'estado' => $this->estado,
            'id_entidad' => $this->id_entidad,
            'moneda' => $this->whenLoaded('moneda', fn () => $this->moneda?->codigo),
            'tipo_combustible' => $this->whenLoaded('tipoCombustible', fn () => $this->tipoCombustible?->nombre),
            'responsable' => $this->whenLoaded('responsable', fn () => trim(($this->responsable?->nombre ?? '').' '.($this->responsable?->apellidos ?? ''))),
            'detalles' => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'id' => $d->id,
                'id_tarjeta' => $d->id_tarjeta,
                'tarjeta' => $d->tarjeta?->numero,
                'saldo_mon' => $d->saldo_mon,
                'saldo_lts' => $d->saldo_lts,
            ])->values()),
        ];
    }
}

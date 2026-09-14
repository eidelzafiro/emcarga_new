<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Aforo expuesto a la API móvil.
 */
class AforoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_parte' => optional($this->fecha_parte)->toDateString(),
            'flete_mt' => $this->flete_mt,
            'flete_mlc' => $this->flete_mlc,
            'flete_demora' => $this->flete_demora,
            'otros_mt' => $this->otros_mt,
            'ingreso_mt' => $this->ingreso_mt,
            'viajes' => $this->viajes,
            'tn_real_total' => $this->tn_real_total,
            'km_total_total' => $this->km_total_total,
            'id_carta_porte' => $this->id_carta_porte,
            'carta_porte' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->numero),
            'cliente' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->cliente?->nombre),
            'tractivo' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->tractivo?->codigo),
            'chofer' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->chofer?->nombre),
            'chofer2' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->chofer2?->nombre),
            'origen' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->lugarOrigen?->nombre),
            'destino' => $this->whenLoaded('cartaPorte', fn () => $this->cartaPorte?->lugarDestino?->nombre),
            'factura' => $this->whenLoaded('factura', fn () => $this->factura?->numero),
            'estado' => $this->id_factura ? 'facturado' : ($this->id_prefactura ? 'prefacturado' : 'pendiente'),
            'indicadores' => $this->whenLoaded('indicadoresFilas', fn () => $this->indicadoresFilas->map(fn ($f) => [
                'posicion' => $f->posicion,
                'tn_pos' => $f->tn_pos,
                'tn_real' => $f->tn_real,
                'km_carga' => $f->km_carga,
                'km_vacio' => $f->km_vacio,
                'km_total' => $f->km_total,
                'traf_pos' => $f->traf_pos,
                'traf_real' => $f->traf_real,
            ])->values()),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dieta extends Model
{
    use SoftDeletes;

    protected $table = 'dietas';

    protected $fillable = [
        'id_bolsa',
        'id_hoja_ruta',
        'folio',
        'fecha',
        'monto',
        'anticipo',
        'f_anticipo',
        'alimentos',
        'hospedaje',
        'otros',
        'id_monedas',
        'id_tractivo',
        'id_reembolso',
        'f_liquidacion',
        'folio_caja',
        'cancelada',
        'id_entidad',
        'tipo_dieta',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'anticipo' => 'decimal:2',
            'f_anticipo' => 'date',
            'alimentos' => 'decimal:2',
            'hospedaje' => 'decimal:2',
            'otros' => 'decimal:2',
            'f_liquidacion' => 'date',
            'cancelada' => 'boolean',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function hojaRuta(): BelongsTo
    {
        return $this->belongsTo(HojasRuta::class, 'id_hoja_ruta');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_monedas');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
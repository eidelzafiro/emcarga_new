<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreTarjeta extends Model
{
    protected $table = 'cierre_tarjetas';

    protected $fillable = [
        'ftrabajo',
        'id_tarjeta',
        'codtm',
        'saldoinicialmon',
        'saldoiniciallts',
        'id_monedas',
        'id_tipo_combustibles',
        'preciomn',
        'saldocargadomon',
        'saldocargadolts',
        'saldodescargadomon',
        'saldodescargadolts',
        'saldotransferenciamon',
        'saldotransferencialts',
        'saldoactualmon',
        'saldoactuallts',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'ftrabajo' => 'date',
            'saldoinicialmon' => 'decimal:2',
            'saldoiniciallts' => 'decimal:2',
            'preciomn' => 'decimal:2',
            'saldocargadomon' => 'decimal:2',
            'saldocargadolts' => 'decimal:2',
            'saldodescargadomon' => 'decimal:2',
            'saldodescargadolts' => 'decimal:2',
            'saldotransferenciamon' => 'decimal:2',
            'saldotransferencialts' => 'decimal:2',
            'saldoactualmon' => 'decimal:2',
            'saldoactuallts' => 'decimal:2',
        ];
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(Tarjeta::class, 'id_tarjeta');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_monedas');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustibles');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}